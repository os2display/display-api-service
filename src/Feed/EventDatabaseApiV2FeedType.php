<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Poster\PosterOutput;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
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
    // TODO: Caching.

    final public const string SUPPORTED_FEED_TYPE = SupportedFeedOutputs::POSTER_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDatabaseApiV2Helper $helper,
    )
    {
    }

    /**
     * @param Feed $feed
     *
     * @return array
     */
    public function getData(Feed $feed): array
    {
        try {
            $feedSource = $feed->getFeedSource();
            $configuration = $feed->getConfiguration();

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

                        if (!empty($locations)) {
                            $queryParams['location.entityId'] = implode(",", array_map(static fn($location) => (int)$location['value'], $locations));
                        }
                        if (!empty($organizers)) {
                            $queryParams['organizer.entityId'] = implode(",", array_map(static fn($organizer) => (int)$organizer['value'], $organizers));
                        }
                        if (!empty($tags)) {
                            $queryParams['tags'] = implode(",", array_map(static fn($tag) => $tag['value'], $tags));
                        }

                        $queryParams['occurrences.start'] = date('c');
                        // TODO: Should be based on (end >= now) instead. But not supported by the API.
                        // $queryParams['occurrences.end'] = date('c');
                        // @see https://github.com/itk-dev/event-database-api/blob/develop/src/Api/Dto/Event.php

                        $members = $this->helper->request($feedSource, "events", $queryParams);

                        $result = [];

                        foreach ($members as $member) {
                            $result[] = $this->helper->mapFirstOccurrenceToOutput((object)$member);
                        }

                        return (new PosterOutput($result))->toArray();
                    case 'single':
                        if (isset($configuration['singleSelectedOccurrence'])) {
                            $occurrenceId = $configuration['singleSelectedOccurrence'];

                            $members = $this->helper->request($feedSource, "occurrences", null, $occurrenceId);

                            if (empty($members)) {
                                return [];
                            }

                            $occurrence = $members[0];

                            return (new PosterOutput([$this->helper->mapOccurrenceToOutput($occurrence)]))->toArray();
                        }
                    default:
                        throw new \Exception("Supported posterType: " . $configuration['posterType'], 400);
                }
            }
        } catch (\Throwable $throwable) {
            // If the content does not exist anymore, unpublished the slide.
            if ($throwable instanceof ClientException && Response::HTTP_NOT_FOUND == $throwable->getCode()) {
                try {
                    // Slide publishedTo is set to now. This will make the slide unpublished from this point on.
                    $feed->getSlide()->setPublishedTo(new \DateTime('now', new \DateTimeZone('UTC')));
                    $this->entityManager->flush();

                    $this->logger->info('Feed with id: {feedId} depends on an item that does not exist in Event Database. Unpublished slide with id: {slideId}', [
                        'feedId' => $feed->getId(),
                        'slideId' => $feed->getSlide()->getId(),
                    ]);
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

        return [];
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
