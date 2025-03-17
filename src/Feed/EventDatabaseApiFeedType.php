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
class EventDatabaseApiFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = SupportedFeedOutputs::POSTER_OUTPUT;
    final public const int REQUEST_TIMEOUT = 10;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function getData(Feed $feed): array
    {
        try {
            $feedSource = $feed->getFeedSource();
            $secrets = $feedSource?->getSecrets();
            $configuration = $feed->getConfiguration();

            if (!isset($secrets['host'])) {
                return [];
            }

            $host = $secrets['host'];

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
                            "$host/api/events",
                            [
                                'timeout' => self::REQUEST_TIMEOUT,
                                'query' => $queryParams,
                            ]
                        );

                        $content = $response->getContent();
                        $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                        return $decoded->{'hydra:member'};
                    case 'single':
                        if (isset($configuration['singleSelectedOccurrence'])) {
                            $occurrenceId = $configuration['singleSelectedOccurrence'];

                            $response = $this->client->request(
                                'GET',
                                "$host$occurrenceId",
                                [
                                    'timeout' => self::REQUEST_TIMEOUT,
                                ]
                            );

                            $content = $response->getContent();
                            $decoded = json_decode($content, null, 512, JSON_THROW_ON_ERROR);

                            $baseUrl = parse_url((string) $decoded->event->{'url'}, PHP_URL_HOST);

                            $eventOccurrence = (object) [
                                'eventId' => $decoded->event->{'@id'},
                                'occurrenceId' => $decoded->{'@id'},
                                'ticketPurchaseUrl' => $decoded->event->{'ticketPurchaseUrl'},
                                'excerpt' => $decoded->event->{'excerpt'},
                                'name' => $decoded->event->{'name'},
                                'url' => $decoded->event->{'url'},
                                'baseUrl' => $baseUrl,
                                'image' => $decoded->event->{'image'},
                                'startDate' => $decoded->{'startDate'},
                                'endDate' => $decoded->{'endDate'},
                                'ticketPriceRange' => $decoded->{'ticketPriceRange'},
                                'eventStatusText' => $decoded->{'eventStatusText'},
                            ];

                            if (isset($decoded->place)) {
                                $eventOccurrence->place = (object) [
                                    'name' => $decoded->place->name,
                                    'streetAddress' => $decoded->place->streetAddress,
                                    'addressLocality' => $decoded->place->addressLocality,
                                    'postalCode' => $decoded->place->postalCode,
                                    'image' => $decoded->place->image,
                                    'telephone' => $decoded->place->telephone,
                                ];
                            }

                            return [$eventOccurrence];
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

            if ('entity' === $name) {
                $path = $request->query->get('path');
                $response = $this->client->request(
                    'GET',
                    "$host$path",
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
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
                    "$host/api/$type",
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
                        'query' => $queryParams,
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
        return [
            'host' => [
                'type' => 'string',
                'exposeValue' => true,
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
                    'format' => 'uri',
                ],
            ],
            'required' => ['host'],
        ];
    }
}
