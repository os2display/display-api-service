<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * See https://github.com/itk-event-database/event-database-api.
 */
class EventDatabaseApiFeedType implements FeedTypeInterface
{
    public const REQUEST_TIMEOUT = 10;

    public function __construct(private FeedService $feedService, private HttpClientInterface $client)
    {
    }

    public function getData(Feed $feed): array|\stdClass|null
    {
        $feedSource = $feed->getFeedSource();
        $secrets = $feedSource->getSecrets();
        $configuration = $this->feedService->getFeedConfiguration($feed);

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

                    $queryParams = [
                        'items_per_page' => $numberOfItems,
                        'occurrences.place.id' => $places,
                        'organizer.id' => $organizers,
                        'tags' => $tags,
                    ];

                    $places && $queryParams['occurrences.place.id'] = $places;
                    $organizers && $queryParams['organizer.id'] = $organizers;
                    $tags && $queryParams['tags'] = $tags;
                    $numberOfItems && $queryParams['items_per_page'] = $numberOfItems;

                    $response = $this->client->request(
                        'GET',
                        "$host/api/events",
                        [
                            'timeout' => self::REQUEST_TIMEOUT,
                            'query' => $queryParams,
                        ]
                    );

                    $content = $response->getContent();
                    $decoded = json_decode($content);

                    $members = $decoded->{'hydra:member'};

                    return $members;
                case 'single':
                    if ($configuration['singleSelectedOccurrence']) {
                        $occurrenceId = $configuration['singleSelectedOccurrence'];

                        $response = $this->client->request(
                            'GET',
                            "$host$occurrenceId",
                            [
                                'timeout' => self::REQUEST_TIMEOUT,
                            ]
                        );

                        $content = $response->getContent();
                        $decoded = json_decode($content);

                        $baseUrl = parse_url($decoded->event->{'url'}, PHP_URL_HOST);

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
                    // no break
                default:
            }
        }

        return [];
    }

    public function getAdminFormOptions(FeedSource $feedSource): ?array
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

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): array|\stdClass|null
    {
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
            $decoded = json_decode($content);

            return $decoded;
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

            $queryParams['occurrences.startDate'] = ['after' => date('Y-m-d')];

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
            $decoded = json_decode($content);

            $members = $decoded->{'hydra:member'};

            $result = [];

            foreach ($members as $member) {
                // Special handling of searching in tags, since EventDatabaseApi does not support this.
                if ('tags' == $type) {
                    if (!isset($queryParams['name']) || str_contains(strtolower($member->name), strtolower($queryParams['name']))) {
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

        return null;
    }

    public function getRequiredSecrets(): array
    {
        return ['host'];
    }

    public function getRequiredConfiguration(): array
    {
        return [];
    }
}
