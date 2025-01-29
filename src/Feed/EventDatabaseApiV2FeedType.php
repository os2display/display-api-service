<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Poster\Occurrence;
use App\Feed\OutputModel\Poster\Place;
use App\Feed\OutputModel\Poster\PosterOption;
use App\Feed\OutputModel\Poster\PosterOutput;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Amp\Iterator\toArray;

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
                        $locations = $configuration['subscriptionPlaceValue'] ?? null;
                        $organizers = $configuration['subscriptionOrganizerValue'] ?? null;
                        $tags = $configuration['subscriptionTagValue'] ?? null;
                        $numberOfItems = $configuration['subscriptionNumberValue'] ?? 5;

                        $queryParams = [
                            'itemsPerPage' => $numberOfItems,
                        ];

                        if (!empty($locations)) {
                            $queryParams['location.entityId'] = implode(",", array_map(static fn ($location) => (int) $location['value'], $locations));
                        }
                        if (!empty($organizers)) {
                            $queryParams['organizer.entityId'] = implode(",", array_map(static fn ($organizer) => (int) $organizer['value'], $organizers));
                        }
                        if (!empty($tags)) {
                            $queryParams['tags'] = implode(",", array_map(static fn ($tag) => $tag['value'], $tags));
                        }

                        // $queryParams['occurrences.start'] = date('c');
                        // TODO: Should be based on end instead. But not supported by the API.
                        // $queryParams['occurrences.end'] = date('c');
                        // @see https://github.com/itk-dev/event-database-api/blob/develop/src/Api/Dto/Event.php

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

                        return (new PosterOutput($result))->toArray();
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

                            return (new PosterOutput([$this->mapOccurrenceToOutput($occurrence)]))->toArray();
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
                    if ('tags' == $type) {
                        $result[] = $displayAsOptions ? new PosterOption(
                            $member->name,
                            (string) $member->name,
                        ) : $this->toEntityResult($type, $member);
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

    private function mapFirstOccurrenceToOutput(object $event): Occurrence
    {
        $occurrence = $event->occurrences[0] ?? null;

        $baseUrl = parse_url((string) $event->url, PHP_URL_HOST);

        $imageUrls = $event->imageUrls ?? [];

        $place = null;

        if (isset($occurrenceData->location)) {
            $place = new Place(
                $occurrence->location->name ?? null,
                $occurrence->location->streetAddress ?? null,
                $occurrence->location->addressLocality ?? null,
                $occurrence->location->postalCode ?? null,
                $occurrence->location->image ?? null,
                $occurrence->location->telephone ?? null,
            );
        }

        return new Occurrence(
            $event->entityId ?? null,
            $entityId ?? null,
            $event->ticketUrl ?? null,
            $event->excerpt ?? null,
            $event->title ?? null,
            $event->url ?? null,
            $baseUrl,
             $imageUrls['large'] ?? null,
             $occurrence->start ?? null,
            $occurrence->end ?? null,
             $occurrence->ticketPriceRange ?? null,
             $occurrence->status ?? null,
            $place,
        );
    }

    private function mapOccurrenceToOutput(object $occurrenceData): Occurrence
    {
        $baseUrl = parse_url((string) $occurrenceData->event->url, PHP_URL_HOST);

        $imageUrls = (object) $occurrenceData->event->imageUrls ?? (object) [];

        $place = null;

        if (isset($occurrenceData->location)) {
            $place = new Place(
                $occurrenceData->location->name ?? null,
                $occurrenceData->location->streetAddress ?? null,
                 $occurrenceData->location->addressLocality ?? null,
                 $occurrenceData->location->postalCode ?? null,
                 $occurrenceData->location->image ?? null,
                 $occurrenceData->location->telephone ?? null,
            );
        }

        return new Occurrence(
            $occurrenceData->event->entityId ?? null,
            $occurrenceData->entityId ?? null,
            $occurrenceData->event->ticketUrl ?? null,
            $occurrenceData->event->excerpt ?? null,
            $occurrenceData->event->title ?? null,
            $occurrenceData->event->url ?? null,
            $baseUrl,
            $imageUrls->large ?? null,
            $occurrenceData->start ?? null,
            $occurrenceData->end ?? null,
            $occurrenceData->ticketPriceRange ?? null,
            $occurrenceData->status ?? null,
            $place,
        );
    }

    private function mapEventToOutput(object $event): object
    {
        return $event;
    }
}
