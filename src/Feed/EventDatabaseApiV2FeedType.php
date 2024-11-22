<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * See https://github.com/itk-event-database/event-database-api.
 */
class EventDatabaseApiV2FeedType implements FeedTypeInterface
{
    final public const SUPPORTED_FEED_TYPE = 'poster';
    final public const REQUEST_TIMEOUT = 10;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param Feed $feed
     *
     * @return array
     */
    public function getData(Feed $feed): array
    {
        try {
            $feedSource = $feed->getFeedSource();
            $secrets = $feedSource?->getSecrets();
            $configuration = $feed->getConfiguration();

            if (!isset($secrets['host']) || !isset($secrets['apikey'])) {
                return [];
            }

            $host = $secrets['host'];
            $apikey = $secrets['apikey'];

            if (isset($configuration['posterType'])) {
                switch ($configuration['posterType']) {
                    case 'subscription':
                        $places = $configuration['subscriptionPlaceValue'] ?? null;
                        $organizers = $configuration['subscriptionOrganizerValue'] ?? null;
                        $tags = $configuration['subscriptionTagValue'] ?? null;
                        $numberOfItems = $configuration['subscriptionNumberValue'] ?? 5;

                        $queryParams = array_filter([
                            'items_per_page' => $numberOfItems,
                            'occurrences.place.id' => array_map(static fn ($place) => str_replace('/api/places/', '', (string) $place['value']), $places),
                            'organizer.id' => array_map(static fn ($organizer) => str_replace('/api/organizers/', '', (string) $organizer['value']), $organizers),
                            'tags' => array_map(static fn ($tag) => str_replace('/api/tags/', '', (string) $tag['value']), $tags),
                        ]);

                        $response = $this->client->request(
                            'GET',
                            "$host/events",
                            [
                                'timeout' => self::REQUEST_TIMEOUT,
                                'query' => $queryParams,
                                'headers' => [
                                    'X-Api-Key' => $apikey,
                                ]
                            ]
                        );

                        $content = $response->getContent();
                        $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                        $members = $decoded->{'hydra:member'} ?? [];

                        return array_map(static fn ($member) => $this->mapOccurrenceToOutput($member), $members);
                    case 'single':
                        if (isset($configuration['singleSelectedOccurrence'])) {
                            $occurrenceId = $configuration['singleSelectedOccurrence'];

                            $response = $this->client->request(
                                'GET',
                                "$host/occurrences/$occurrenceId",
                                [
                                    'timeout' => self::REQUEST_TIMEOUT,
                                    'headers' => [
                                        'X-Api-Key' => $apikey,
                                    ]
                                ]
                            );

                            $content = $response->getContent();
                            $decoded = json_decode($content, null, 512, JSON_THROW_ON_ERROR);

                            $occurrence = $decoded->{'hydra:member'}[0];

                            return [$this->mapOccurrenceToOutput($occurrence)];
                        }
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

    private function mapOccurrenceToOutput(object $occurrence): object
    {
        $baseUrl = parse_url((string) $occurrence->event->url, PHP_URL_HOST);

        $imageUrls = $occurrence->event->imageUrls ?? (object) [];

        $eventOccurrence = (object) [
            'eventId' => $occurrence->event->entityId ?? null,
            'occurrenceId' => $occurrence->entityId ?? null,
            'ticketPurchaseUrl' => $occurrence->event->ticketUrl ?? null,
            'excerpt' => $occurrence->event->excerpt ?? null,
            'name' => $occurrence->event->title ?? null,
            'url' => $occurrence->event->url ?? null,
            'baseUrl' => $baseUrl,
            'image' => $imageUrls->large ?? null,
            'startDate' => $occurrence->start ?? null,
            'endDate' => $occurrence->end ?? null,
            'ticketPriceRange' => $occurrence->ticketPriceRange ?? null,
            'eventStatusText' => $occurrence->status ?? null,
        ];

        if (isset($occurrence->location)) {
            $eventOccurrence->location = (object) [
                'name' => $occurrence->location->name ?? null,
                'streetAddress' => $occurrence->location->streetAddress ?? null,
                'addressLocality' => $occurrence->location->addressLocality ?? null,
                'postalCode' => $occurrence->location->postalCode ?? null,
                'image' => $occurrence->location->image ?? null,
                'telephone' => $occurrence->location->telephone ?? null,
            ];
        }

        return $eventOccurrence;
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $searchEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'search');
        $endpointEntity = $this->feedService->getFeedSourceConfigUrl($feedSource, 'entity');

        // @TODO: Translation.
        return [
            [
                'key' => 'poster-selector',
                'input' => 'poster-selector',
                'endpointSearch' => $searchEndpoint,
                'endpointEntity' => $endpointEntity,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resourcer der skal hentes indgange fra.',
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
            $secrets = $feedSource->getSecrets();

            if (!isset($secrets['host'])) {
                return [];
            }

            $host = $secrets['host'];
            $apikey = $secrets['apikey'];

            if ('entity' === $name) {
                $path = $request->query->get('path');
                $response = $this->client->request(
                    'GET',
                    "$host$path",
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
                        'headers' => [
                            'X-Api-Key' => $apikey,
                        ]
                    ]
                );

                $content = $response->getContent();

                return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } elseif ('search' === $name) {
                $queryParams = $request->query->all();
                $type = $queryParams['type'];
                $displayAsOptions = isset($queryParams['display']) && 'options' == $queryParams['display'];

                unset($queryParams['type']);

                if ($displayAsOptions) {
                    unset($queryParams['display']);
                }

                if ('events' == $type) {
                    if (isset($queryParams['tag'])) {
                        $tag = $queryParams['tag'];
                        unset($queryParams['tag']);
                        $queryParams['tags'] = $tag;
                    }

                    if (isset($queryParams['organizer'])) {
                        $organizer = $queryParams['organizer'];
                        unset($queryParams['organizer']);
                        $queryParams['organizer.id'] = $organizer;
                    }

                    if (isset($queryParams['place'])) {
                        $place = $queryParams['place'];
                        unset($queryParams['place']);
                        $queryParams['occurrences.place.id'] = $place;
                    }
                }

                $queryParams['occurrences.endDate'] = ['after' => date('Y-m-d')];

                !isset($queryParams['items_per_page']) && $queryParams['items_per_page'] = 10;

                $response = $this->client->request(
                    'GET',
                    "$host/$type",
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
                        'query' => $queryParams,
                        'headers' => [
                            'X-Api-Key' => $apikey,
                        ]
                    ]
                );

                $content = $response->getContent();
                $decoded = json_decode($content, null, 512, JSON_THROW_ON_ERROR);

                $members = $decoded->{'hydra:member'};

                $result = [];

                foreach ($members as $member) {
                    // Special handling of searching in tags, since EventDatabaseApi does not support this.
                    if ('tags' == $type) {
                        if (!isset($queryParams['name']) || str_contains(strtolower((string) $member->name), strtolower((string) $queryParams['name']))) {
                            $result[] = $displayAsOptions ? [
                                'label' => $member->name,
                                'value' => $member->{'@id'},
                            ] : $member;
                        }
                    } else {
                        $result[] = $displayAsOptions ? [
                            'label' => $member->name,
                            'value' => $member->{'@id'},
                        ] : $member;
                    }
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
        return ['host', 'apikey'];
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
}
