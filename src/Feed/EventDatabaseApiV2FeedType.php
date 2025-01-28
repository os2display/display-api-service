<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\EventDatabaseApiV2\PosterOption;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @see https://github.com/itk-dev/event-database-api
 * @see https://github.com/itk-dev/event-database-imports
 */
class EventDatabaseApiV2FeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = SupportedFeedOutputs::POSTER_OUTPUT;
    final public const int REQUEST_TIMEOUT = 10;

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

                        $queryParams = [
                            'itemsPerPage' => $numberOfItems,
                        ];

                        if (!empty($places)) {
                            $queryParams['location.entityId'] = implode(",", array_map(static fn ($place) => (int) $place['value'], $places));
                        }
                        if (!empty($organizers)) {
                            $queryParams['organizer.entityId'] = implode(",", array_map(static fn ($organizer) => (int) $organizer['value'], $organizers));
                        }
                        if (!empty($tags)) {
                            $queryParams['tags'] = implode(",", array_map(static fn ($tag) => $tag['value'], $tags));
                        }

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

                        $content = $response->toArray();

                        $members = $content['hydra:member'];

                        $result = [];

                        foreach ($members as $member) {
                            $result[] = $this->mapFirstOccurrenceToOutput((object) $member);
                        }

                        return $result;
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

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $searchEndpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'search');
        $endpointEntity = $this->feedService->getFeedSourceConfigUrl($feedSource, 'entity');

        return [
            [
                'key' => 'poster-selector-v2',
                'input' => 'poster-selector-v2',
                'endpointSearch' => $searchEndpoint,
                'endpointEntity' => $endpointEntity,
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
            $secrets = $feedSource->getSecrets();

            if (!isset($secrets['host'])) {
                return [];
            }

            $host = $secrets['host'];
            $apikey = $secrets['apikey'];

            if ('entity' === $name) {
                $entityType = $request->query->get('entityType');
                $entityId = $request->query->get('entityId');
                $response = $this->client->request(
                    'GET',
                    "$host/$entityType/$entityId",
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
                        'headers' => [
                            'X-Api-Key' => $apikey,
                        ]
                    ]
                );

                $content = $response->getContent();
                $decoded = json_decode($content, null, 512, JSON_THROW_ON_ERROR);

                $members = $decoded->{'hydra:member'};

                $member = array_pop($members);

                return [$this->toEntityResult($entityType, $member)];
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

                    if (isset($queryParams['organization'])) {
                        $organizer = $queryParams['organization'];
                        unset($queryParams['organization']);
                        $queryParams['organizer.entityId'] = (int) $organizer;
                    }

                    if (isset($queryParams['location'])) {
                        $location = $queryParams['location'];
                        unset($queryParams['location']);
                        $queryParams['location.entityId'] = (int) $location;
                    }

                    // $queryParams['occurrences.start'] = date('c');
                    // TODO: Should be based on end instead. But not supported by the API.
                    // $queryParams['occurrences.end'] = date('c');
                    // @see https://github.com/itk-dev/event-database-api/blob/develop/src/Api/Dto/Event.php
                }

                if (!isset($queryParams['itemsPerPage'])) {
                    $queryParams['itemsPerPage'] = 10;
                }

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
                            $result[] = $displayAsOptions ? new PosterOption(
                                $member->name,
                                (string) $member->name,
                            ) : $this->toEntityResult($type, $member);
                        }
                    } else {
                        $result[] = $displayAsOptions ? new PosterOption(
                            $member->name,
                            (string) $member->entityId,
                        ) : $this->toEntityResult($type, $member);
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

    private function toEntityResult(string $entityType, object $entity): object
    {
        return match ($entityType) {
            'occurrences' => $this->mapOccurrenceToOutput($entity),
            'events' => $this->mapEventToOutput($entity),
            default => throw new \Exception("Unknown entity type."),
        };
    }

    private function mapFirstOccurrenceToOutput(object $event): object
    {
        $occurrence = $event->occurrences[0] ?? null;

        $baseUrl = parse_url((string) $event->url, PHP_URL_HOST);

        $imageUrls = $event->imageUrls ?? [];

        $eventOccurrence = (object) [
            'eventId' => $event->entityId ?? null,
            'occurrenceId' => $entityId ?? null,
            'ticketPurchaseUrl' => $event->ticketUrl ?? null,
            'excerpt' => $event->excerpt ?? null,
            'name' => $event->title ?? null,
            'url' => $event->url ?? null,
            'baseUrl' => $baseUrl,
            'image' => $imageUrls['large'] ?? null,
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

    private function mapOccurrenceToOutput(object $occurrence): object
    {
        $baseUrl = parse_url((string) $occurrence->event->url, PHP_URL_HOST);

        $imageUrls = (object) $occurrence->event->imageUrls ?? (object) [];

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

    private function mapEventToOutput(object $event): object
    {
        return $event;
    }
}
