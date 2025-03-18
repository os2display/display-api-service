<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Poster\PosterOutput;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/itk-dev/event-database-api
 * @see https://github.com/itk-dev/event-database-imports
 */
class EventDatabaseApiV2FeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = SupportedFeedOutputs::POSTER_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDatabaseApiV2Helper $helper,
        private readonly CacheItemPoolInterface $feedWithoutExpireCache,
        private readonly int $cacheExpire,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getData(Feed $feed): array
    {
        $cacheKey = 'eventdb2-'.$feed->getId();
        $cacheKeyLatestFetch = 'eventdb2-latest-fetch-'.$feed->getId();

        $cacheItem = $this->feedWithoutExpireCache->getItem($cacheKey);
        $latestFetchCacheItem = $this->feedWithoutExpireCache->getItem($cacheKeyLatestFetch);

        // Serve cached item if latestFetchCacheItem has not expired and feed has not changed.
        // The expiration is set on latestFetchCacheItem and not cacheItem, so cacheItem can be used as fallback.
        if ($latestFetchCacheItem->isHit()) {
            // If feed has not been modified since the item was cached.
            if ($feed->getModifiedAt()?->format('c') == $latestFetchCacheItem->get()) {
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                }
            }
        }

        try {
            $feedSource = $feed->getFeedSource();
            $configuration = $feed->getConfiguration();

            if (null === $feedSource) {
                throw new \Exception('Feed source is null');
            }

            if (isset($configuration['posterType'])) {
                switch ($configuration['posterType']) {
                    case 'subscription':
                        $locations = $configuration['subscriptionPlaceValue'] ?? null;
                        $organizers = $configuration['subscriptionOrganizerValue'] ?? null;
                        $tags = $configuration['subscriptionTagValue'] ?? null;
                        $numberOfItems = $configuration['subscriptionNumberValue'] ?? 5;

                        $queryParams = [
                            'itemsPerPage' => $numberOfItems,
                        ];

                        if (is_array($locations) && count($locations) > 0) {
                            $queryParams['location.entityId'] = implode(',', array_map(static fn ($location) => (int) $location['value'], $locations));
                        }
                        if (is_array($organizers) && count($organizers) > 0) {
                            $queryParams['organizer.entityId'] = implode(',', array_map(static fn ($organizer) => (int) $organizer['value'], $organizers));
                        }
                        if (is_array($tags) && count($tags) > 0) {
                            $queryParams['tags'] = implode(',', array_map(static fn ($tag) => (string) $tag['value'], $tags));
                        }

                        $queryParams['occurrences.start'] = date('c');
                        // TODO: Should be based on (end >= now) instead. But not supported by the API.
                        // $queryParams['occurrences.end'] = date('c');
                        // @see https://github.com/itk-dev/event-database-api/blob/develop/src/Api/Dto/Event.php

                        $members = $this->helper->request($feedSource, 'events', $queryParams);

                        $result = [];

                        foreach ($members as $member) {
                            $poster = $this->helper->mapFirstOccurrenceToOutput((object) $member);
                            if (null !== $poster) {
                                $result[] = $poster;
                            }
                        }

                        $posterOutput = (new PosterOutput($result))->toArray();

                        $cacheItem->set($posterOutput);
                        $latestFetchCacheItem->expiresAfter($this->cacheExpire)->set($feed->getModifiedAt()?->format('c') ?? '');
                        $this->feedWithoutExpireCache->save($cacheItem);
                        $this->feedWithoutExpireCache->save($latestFetchCacheItem);

                        return $posterOutput;
                    case 'single':
                        if (isset($configuration['singleSelectedOccurrence'])) {
                            $occurrenceId = $configuration['singleSelectedOccurrence'];

                            $members = $this->helper->request($feedSource, 'occurrences', null, $occurrenceId);

                            if (empty($members)) {
                                return [];
                            }

                            $occurrenceData = $members[0];

                            $result = [];

                            $occurrence = $this->helper->mapOccurrenceToOutput($occurrenceData);

                            if (null !== $occurrence) {
                                $result[] = $occurrence;
                            }

                            $posterOutput = (new PosterOutput($result))->toArray();

                            $cacheItem->set($posterOutput);
                            $latestFetchCacheItem->expiresAfter($this->cacheExpire)->set($feed->getModifiedAt()?->format('c') ?? '');
                            $this->feedWithoutExpireCache->save($cacheItem);
                            $this->feedWithoutExpireCache->save($latestFetchCacheItem);

                            return $posterOutput;
                        }
                        // no break
                    default:
                        throw new \Exception('Supported posterType: '.$configuration['posterType'], 400);
                }
            }
        } catch (\Throwable $throwable) {
            // If the content does not exist anymore, unpublished the slide.
            if ($throwable instanceof ClientException && Response::HTTP_NOT_FOUND == $throwable->getCode()) {
                try {
                    $slide = $feed->getSlide();

                    if (null !== $slide) {
                        // Slide publishedTo is set to now. This will make the slide unpublished from this point on.
                        $slide->setPublishedTo(new \DateTime('now', new \DateTimeZone('UTC')));
                        $this->entityManager->flush();

                        $this->logger->info('Feed with id: {feedId} depends on an item that does not exist in Event Database. Unpublished slide with id: {slideId}', [
                            'feedId' => $feed->getId(),
                            'slideId' => $slide->getId(),
                        ]);
                    }
                } catch (\Exception $exception) {
                    $this->logger->error('{code}: {message}', [
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                    ]);
                }
            } else {
                $this->logger->error('{code}: {message}', [
                    'code' => $throwable->getCode(),
                    'message' => $throwable->getMessage(),
                ]);
            }
        }

        // Fallback option is to return the cached data.
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        } else {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $searchEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'search');
        $entityEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'entity');
        $optionsEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'options');

        return [
            [
                'key' => 'poster-selector-v2',
                'input' => 'poster-selector-v2',
                'endpointSearch' => $searchEndpoint,
                'endpointEntity' => $entityEndpoint,
                'endpointOption' => $optionsEndpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resurser der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('entity' === $name) {
                $entityType = $request->query->get('entityType');
                $entityId = $request->query->get('entityId');

                if (null === $entityType || null === $entityId) {
                    throw new \Exception('entityType and entityId must not be null');
                }

                $members = $this->helper->request($feedSource, $entityType, null, (int) $entityId);

                $result = [];

                if (count($members) > 0) {
                    $member = array_pop($members);
                    $result[] = $this->helper->toEntityResult($entityType, $member);
                }

                return $result;
            } elseif ('options' === $name) {
                $entityType = $request->query->get('entityType');

                $query = [
                    'itemsPerPage' => 50,
                    'name' => $request->query->get('search') ?? '',
                ];

                if (null === $entityType) {
                    throw new \Exception('entityType must not be null');
                }

                $members = $this->helper->request($feedSource, $entityType, $query);

                $result = [];

                foreach ($members as $member) {
                    $result[] = $this->helper->toPosterOption($member, $entityType);
                }

                return $result;
            } elseif ('search' === $name) {
                $query = $request->query->all();
                $queryParams = [];

                $type = $query['type'];

                if ('events' == $type) {
                    if (isset($query['title'])) {
                        $queryParams['title'] = $query['title'];
                    }

                    if (isset($query['tag'])) {
                        $tag = $query['tag'];
                        $queryParams['tags'] = $tag;
                    }

                    if (isset($query['organization'])) {
                        $organizer = $query['organization'];
                        $queryParams['organizer.entityId'] = (int) $organizer;
                    }

                    if (isset($query['location'])) {
                        $location = $query['location'];
                        $queryParams['location.entityId'] = (int) $location;
                    }

                    $queryParams['occurrences.start'] = date('c');
                    // TODO: Should be based on (end >= now) instead. But not supported by the API.
                    // $queryParams['occurrences.end'] = date('c');
                    // @see https://github.com/itk-dev/event-database-api/blob/develop/src/Api/Dto/Event.php
                }

                $queryParams['itemsPerPage'] = $query['itemsPerPage'] ?? 10;

                $members = $this->helper->request($feedSource, $type, $queryParams);

                $result = [];

                foreach ($members as $member) {
                    $result[] = $this->helper->toEntityResult($type, $member);
                }

                return $result;
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredSecrets(): array
    {
        return [
            'host' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
            'apikey' => [
                'type' => 'string',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredConfiguration(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'host' => [
                    'type' => 'string',
                ],
                'apikey' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['host', 'apikey'],
        ];
    }
}
