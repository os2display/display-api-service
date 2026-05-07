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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Feed type emitting POSTER_OUTPUT for events fetched from a KK External
 * Entities Event platform.
 *
 * @see kkevent-external-entities-README.pdf
 */
class KkEventFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::POSTER_OUTPUT;

    private const int CACHE_OPTIONS_TTL = 60 * 60;

    private const array TAXONOMY_TYPES = ['tags', 'categories', 'target_groups', 'district'];

    public function __construct(
        private readonly FeedService $feedService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly KkEventHelper $helper,
        private readonly CacheInterface $feedWithoutExpireCache,
        private readonly int $cacheExpire,
    ) {}

    public function getData(Feed $feed): array
    {
        $modifiedAt = $feed->getModifiedAt()?->getTimestamp() ?? 0;
        $cacheKey = 'kkevent-'.$feed->getId().'-'.$modifiedAt;

        try {
            return $this->feedWithoutExpireCache->get($cacheKey, function (ItemInterface $item) use ($feed) {
                $item->expiresAfter($this->cacheExpire);

                $feedSource = $feed->getFeedSource();
                $configuration = $feed->getConfiguration();

                if (null === $feedSource) {
                    throw new \RuntimeException('KkEventFeedType: Feed source is null.');
                }

                if (!isset($configuration['posterType'])) {
                    throw new \RuntimeException('KkEventFeedType: posterType is not set.');
                }

                return match ($configuration['posterType']) {
                    'subscription' => $this->getSubscriptionPosterOutput($feedSource, $configuration),
                    'single' => $this->getSinglePosterOutput($feedSource, $configuration),
                    default => throw new \RuntimeException('Unsupported posterType: '.$configuration['posterType']),
                };
            });
        } catch (\Throwable $throwable) {
            if ($throwable instanceof ClientException && Response::HTTP_NOT_FOUND === $throwable->getCode()) {
                try {
                    $slide = $feed->getSlide();
                    if (null !== $slide) {
                        $slide->setPublishedTo(new \DateTime('now', new \DateTimeZone('UTC')));
                        $this->entityManager->flush();
                        $this->logger->info('Feed with id: {feedId} depends on a KK event that no longer exists. Unpublished slide with id: {slideId}', [
                            'feedId' => $feed->getId(),
                            'slideId' => $slide->getId(),
                        ]);
                    }
                } catch (\Exception $exception) {
                    $this->logger->error('KkEventFeedType: Failed to unpublish slide for feed {feedId}: {message}', [
                        'feedId' => $feed->getId(),
                        'message' => $exception->getMessage(),
                        'exception' => $exception,
                    ]);
                }
            } else {
                $this->logger->error('KkEventFeedType: Failed to get data for feed {feedId}: {message}', [
                    'feedId' => $feed->getId(),
                    'message' => $throwable->getMessage(),
                    'exception' => $throwable,
                ]);
            }

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<\App\Feed\OutputModel\Poster\Poster>
     */
    private function getSubscriptionPosterOutput(FeedSource $feedSource, array $configuration): array
    {
        $numberOfItems = isset($configuration['subscriptionNumberValue']) ? (int) $configuration['subscriptionNumberValue'] : 5;
        $queryParams = $this->buildTaxonomyQueryParams([
            'tags' => $configuration['subscriptionTagValue'] ?? null,
            'categories' => $configuration['subscriptionCategoryValue'] ?? null,
            'target_groups' => $configuration['subscriptionTargetGroupValue'] ?? null,
            'district' => $configuration['subscriptionDistrictValue'] ?? null,
        ]);

        $result = $this->getSubscriptionData($feedSource, $queryParams, $numberOfItems);

        return (new PosterOutput($result))->toArray();
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<\App\Feed\OutputModel\Poster\Poster>
     */
    private function getSinglePosterOutput(FeedSource $feedSource, array $configuration): array
    {
        if (!isset($configuration['singleSelectedEvent'])) {
            return [];
        }

        $uuid = (string) $configuration['singleSelectedEvent'];
        $response = $this->helper->request($feedSource, 'events', null, $uuid);
        $items = $this->helper->extractItems($response);

        if (0 === count($items) || !is_array($items[0])) {
            return [];
        }

        $poster = $this->helper->mapEventToPoster($items[0]);
        $result = null !== $poster ? [$poster] : [];

        return (new PosterOutput($result))->toArray();
    }

    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $searchEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'search');
        $entityEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'entity');
        $optionsEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'options');
        $subscriptionEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'subscription');

        return [
            [
                'key' => 'poster-selector-v2',
                'input' => 'poster-selector-v2',
                'endpointSearch' => $searchEndpoint,
                'endpointEntity' => $entityEndpoint,
                'endpointOption' => $optionsEndpoint,
                'endpointSubscription' => $subscriptionEndpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke begivenheder fra KK External Entities Event der skal vises.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            return match ($name) {
                'entity' => $this->getEntityOption($request, $feedSource),
                'options' => $this->getTaxonomyOptions($request, $feedSource),
                'subscription' => $this->getSubscriptionPreview($request, $feedSource),
                'search' => $this->getSearchResults($request, $feedSource),
                default => null,
            };
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);
        }

        return null;
    }

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

    public function getRequiredConfiguration(): array
    {
        return [];
    }

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
                'host' => ['type' => 'string'],
                'apikey' => ['type' => 'string'],
            ],
            'required' => ['host', 'apikey'],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getEntityOption(Request $request, FeedSource $feedSource): array
    {
        $entityType = $request->query->get('entityType');
        $entityId = $request->query->get('entityId');

        if (null === $entityType || null === $entityId) {
            throw new \RuntimeException('entityType and entityId must not be null');
        }

        $response = $this->helper->request($feedSource, $entityType, null, (string) $entityId);
        $items = $this->helper->extractItems($response);

        $result = [];
        if (count($items) > 0) {
            $entity = $this->helper->toEntityResult($entityType, $items[0]);
            if (null !== $entity) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * @return array<\App\Feed\OutputModel\Poster\PosterOption>
     */
    private function getTaxonomyOptions(Request $request, FeedSource $feedSource): array
    {
        $entityType = $request->query->get('entityType');

        if (null === $entityType) {
            throw new \RuntimeException('entityType must not be null');
        }

        if (!in_array($entityType, self::TAXONOMY_TYPES, true)) {
            throw new BadRequestHttpException('Unsupported entityType: '.$entityType);
        }

        return $this->feedWithoutExpireCache->get('kkevent_options_'.$entityType, function (ItemInterface $item) use ($feedSource, $entityType) {
            $item->expiresAfter(self::CACHE_OPTIONS_TTL);

            $page = 1;
            $itemsPerPage = 50;
            $results = [];

            do {
                $response = $this->helper->request($feedSource, $entityType, [
                    'itemsPerPage' => $itemsPerPage,
                    'page' => $page,
                ]);
                $members = $this->helper->extractItems($response);

                foreach ($members as $member) {
                    $normalized = is_array($member) ? $member : ['name' => (string) $member];
                    $results[] = $this->helper->toPosterOption($normalized);
                }

                $total = $this->helper->extractTotalItems($response, count($results));
                $fetchMore = count($members) === $itemsPerPage && $total > $page * $itemsPerPage;
                ++$page;
            } while ($fetchMore);

            return $results;
        });
    }

    /**
     * @return array<\App\Feed\OutputModel\Poster\Poster>
     */
    private function getSubscriptionPreview(Request $request, FeedSource $feedSource): array
    {
        $query = $request->query->all();
        $queryParams = $this->buildTaxonomyQueryParams([
            'tags' => $query['tag'] ?? null,
            'categories' => $query['category'] ?? null,
            'target_groups' => $query['target_group'] ?? null,
            'district' => $query['district'] ?? null,
        ]);
        $numberOfItems = isset($query['numberOfItems']) ? (int) $query['numberOfItems'] : 10;

        return $this->getSubscriptionData($feedSource, $queryParams, $numberOfItems);
    }

    /**
     * @return array<mixed>
     */
    private function getSearchResults(Request $request, FeedSource $feedSource): array
    {
        $query = $request->query->all();
        $type = is_string($query['type'] ?? null) ? $query['type'] : 'events';
        $queryParams = [];

        if ('events' === $type) {
            if (isset($query['title'])) {
                $queryParams['title'] = $query['title'];
            }
            $taxonomyMap = [
                'tag' => 'tags',
                'category' => 'categories',
                'target_group' => 'target_groups',
                'district' => 'district',
            ];
            foreach ($taxonomyMap as $from => $to) {
                if (!isset($query[$from])) {
                    continue;
                }
                $value = $query[$from];
                $queryParams[$to] = is_array($value) ? implode(',', array_map(static fn ($v) => (string) $v, $value)) : (string) $value;
            }
        }

        $queryParams['itemsPerPage'] = $query['itemsPerPage'] ?? 10;

        $response = $this->helper->request($feedSource, $type, $queryParams);
        $members = $this->helper->extractItems($response);

        $result = [];
        foreach ($members as $member) {
            $entity = $this->helper->toEntityResult($type, $member);
            if (null !== $entity) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $queryParams
     *
     * @return array<\App\Feed\OutputModel\Poster\Poster>
     */
    private function getSubscriptionData(FeedSource $feedSource, array $queryParams, int $numberOfItems): array
    {
        $itemsPerPage = 20;
        $page = 1;
        $result = [];
        $addedUuids = [];

        $queryParams['itemsPerPage'] = $itemsPerPage;

        do {
            $queryParams['page'] = $page;

            $response = $this->helper->request($feedSource, 'events', $queryParams);
            $members = $this->helper->extractItems($response);

            foreach ($members as $member) {
                if (!is_array($member)) {
                    continue;
                }
                $poster = $this->helper->mapEventToPoster($member);
                if (null === $poster) {
                    continue;
                }
                $eventId = (string) $poster->eventId;
                if (in_array($eventId, $addedUuids, true)) {
                    continue;
                }
                $addedUuids[] = $eventId;
                $result[] = $poster;
                if (count($result) >= $numberOfItems) {
                    break;
                }
            }

            $total = $this->helper->extractTotalItems($response, count($result));
            $hasMore = count($members) === $itemsPerPage && $total > $page * $itemsPerPage;
            $fetchMore = count($result) < $numberOfItems && $hasMore;
            ++$page;
        } while ($fetchMore);

        return $result;
    }

    /**
     * @param array<string, mixed> $byTaxonomy
     *
     * @return array<string, string>
     */
    private function buildTaxonomyQueryParams(array $byTaxonomy): array
    {
        $params = [];
        foreach ($byTaxonomy as $key => $value) {
            if (!is_array($value) || 0 === count($value)) {
                continue;
            }
            $values = array_map(static function ($v) {
                if (is_array($v) && isset($v['value'])) {
                    return (string) $v['value'];
                }

                return (string) $v;
            }, $value);
            $params[$key] = implode(',', $values);
        }

        return $params;
    }
}
