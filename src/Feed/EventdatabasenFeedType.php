<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * See https://github.com/itk-event-database/event-database-api
 */
class EventdatabasenFeedType implements FeedTypeInterface
{
    public const REQUEST_TIMEOUT = 10;

    public function __construct(private FeedService $feedService, private HttpClientInterface $client)
    {
    }

    public function getData(Feed $feed): ?array
    {
        $feedSource = $feed->getFeedSource();
        $secrets = $feedSource->getSecrets();
        $configuration = $this->feedService->getFeedConfiguration($feed);

        if (isset($configuration['posterType'])) {
            if ($configuration['posterType'] == 'single') {
                if ($configuration['singleSelectedOccurrence']) {
                    $occurrenceId = $configuration['singleSelectedOccurrence'];

                    $response = $this->client->request(
                        'GET',
                        // TODO: Get url from FeedSource configuration.
                        "https://api.detskeriaarhus.dk$occurrenceId",
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
                        'excerpt' =>  $decoded->event->{'excerpt'},
                        'name' =>  $decoded->event->{'name'},
                        'url' =>  $decoded->event->{'url'},
                        'baseUrl' => $baseUrl,
                        'image' =>  $decoded->event->{'image'},
                        'startDate' =>  $decoded->{'startDate'},
                        'endDate' =>  $decoded->{'endDate'},
                        'ticketPriceRange' =>  $decoded->{'ticketPriceRange'},
                        'eventStatusText' =>  $decoded->{'eventStatusText'},
                    ];

                    if (isset($decoded->place)) {
                        $eventOccurrence->place = (object)[
                            'name' => $decoded->place->name,
                            'streetAddress' => $decoded->place->streetAddress,
                            'addressLocality' => $decoded->place->addressLocality,
                            'postalCode' => $decoded->place->postalCode,
                            'image' => $decoded->place->image,
                            'telephone' => $decoded->place->telephone,
                        ];
                    }

                    return $eventOccurrence;
                }

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
        if ('entity' === $name) {
            $path = $request->query->get('path');
            $response = $this->client->request(
                'GET',
                // TODO: Get url from FeedSource configuration.
                "https://api.detskeriaarhus.dk$path",
                [
                    'timeout' => self::REQUEST_TIMEOUT,
                ]
            );

            $content = $response->getContent();
            $decoded = json_decode($content);

            return $decoded;
        } else if ('search' === $name) {
            $queryParams = $request->query->all();
            $type = $queryParams['type'];
            $displayAsOptions = isset($queryParams['display']) && $queryParams['display'] == 'options';

            unset($queryParams['type']);

            if ($displayAsOptions) {
                unset($queryParams['display']);
            }

            if ($type == 'events') {
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

            $response = $this->client->request(
                'GET',
                // TODO: Get url from FeedSource configuration.
                "https://api.detskeriaarhus.dk/api/$type",
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
                // Special handling of searching in tags, since Eventdatabasen does not support this.
                if ($type == 'tags') {
                    if (str_contains(strtolower($member->name), strtolower($queryParams['name']))) {
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
        return [];
    }

    public function getRequiredConfiguration(): array
    {
        return [];
    }
}
