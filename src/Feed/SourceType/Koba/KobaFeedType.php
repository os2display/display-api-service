<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Koba;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\FeedTypeInterface;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** @deprecated */
class KobaFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::CALENDAR_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param Feed $feed
     *
     * @return array
     */
    public function getData(Feed $feed): array
    {
        try {
            $results = [];

            $feedSource = $feed->getFeedSource();
            $secrets = $feedSource?->getSecrets();
            $configuration = $feed->getConfiguration();

            if (!isset($secrets['kobaHost']) || !isset($secrets['kobaApiKey'])) {
                $this->logger->error('KobaFeedType: "Host" and "ApiKey" not configured.');

                return [];
            }

            $kobaHost = $secrets['kobaHost'];
            $kobaApiKey = $secrets['kobaApiKey'];
            $kobaGroup = $secrets['kobaGroup'] ?? 'default';
            $filterList = $configuration['filterList'] ?? false;
            $rewriteBookedTitles = $configuration['rewriteBookedTitles'] ?? false;

            if (!isset($configuration['resources'])) {
                $this->logger->error('KobaFeedType: Resources not set.');

                return [];
            }

            $resources = $configuration['resources'];

            // Round down to the nearest hour.
            $from = time() - (time() % 3600);

            // Get bookings for the coming week.
            // @TODO: Support for configuring interest period.
            $to = $from + 7 * 24 * 60 * 60;

            foreach ($resources as $resource) {
                try {
                    $bookings = $this->getBookingsFromResource($kobaHost, $kobaApiKey, $resource, $kobaGroup, $from, $to);
                } catch (\Throwable $throwable) {
                    $this->logger->error('KobaFeedType: Get bookings from resources failed. Code: {code}, Message: {message}', [
                        'code' => $throwable->getCode(),
                        'message' => $throwable->getMessage(),
                    ]);
                    continue;
                }

                foreach ($bookings as $booking) {
                    $title = $booking['event_name'] ?? '';

                    if (!is_string($title)) {
                        $this->logger->error('KobaFeedType: event_name is not string.');

                        throw new \InvalidArgumentException('Koba event_name is not string');
                    }

                    // Apply list filter. If enabled it removes all events that do not have (liste) in title.
                    if (true === $filterList) {
                        if (!str_contains($title, '(liste)')) {
                            continue;
                        } else {
                            $title = str_replace('(liste)', '', $title);
                        }
                    }

                    // Apply booked title override. If enabled it changes the title to Optaget if it contains (optaget).
                    if (true === $rewriteBookedTitles) {
                        if (str_contains($title, '(optaget)')) {
                            $title = 'Optaget';
                        }
                    }

                    $results[] = [
                        'id' => Ulid::generate(),
                        'title' => $title,
                        'description' => $booking['event_description'] ?? '',
                        'startTime' => $booking['start_time'] ?? '',
                        'endTime' => $booking['end_time'] ?? '',
                        'resourceTitle' => $booking['resource_alias'] ?? '',
                        'resourceId' => $booking['resource_id'] ?? '',
                    ];
                }
            }

            // Sort bookings by start time.
            usort($results, fn ($a, $b) => strcmp((string) $a['startTime'], (string) $b['startTime']));

            return $results;
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'resources');

        // @TODO: Translation.
        return [
            [
                'key' => 'koba-resource-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $endpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resourcer der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
            [
                'key' => 'koba-resource-rewrite-booked',
                'input' => 'checkbox',
                'name' => 'rewriteBookedTitles',
                'label' => 'Omskriv titler med (optaget)',
                'helpText' => 'Denne mulighed gør at titler som indeholder (optaget) bliver omskrevet til "Optaget".',
                'formGroupClasses' => 'col mb-3',
            ],
            [
                'key' => 'koba-resource-filter-not-list',
                'input' => 'checkbox',
                'name' => 'filterList',
                'label' => 'Vis kun begivenheder med (liste) i titlen',
                'helpText' => 'Denne mulighed fjerner begivenheder der IKKE har (liste) i titlen. Den fjerner også (liste) fra titlen.',
                'formGroupClasses' => 'col mb-3',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('resources' === $name) {
                $secrets = $feedSource->getSecrets();

                if (!isset($secrets['kobaHost']) || !isset($secrets['kobaApiKey'])) {
                    return [];
                }

                $kobaHost = $secrets['kobaHost'];
                $kobaApiKey = $secrets['kobaApiKey'];
                $kobaGroup = $secrets['kobaGroup'] ?? 'default';

                $requestUrl = "$kobaHost/api/resources/group/$kobaGroup";

                $response = $this->client->request('GET', $requestUrl, [
                    'query' => [
                        'apikey' => $kobaApiKey,
                    ],
                ]);

                $content = $response->toArray();

                $resources = [];

                foreach ($content as $entry) {
                    // Ignore entries without mail.
                    if (empty($entry['mail'])) {
                        continue;
                    }

                    $mail = $entry['mail'];
                    $alias = !empty($entry['alias']) ? " ({$entry['alias']})" : '';
                    $name = $entry['name'] ?? $mail;

                    // Make sure a title has been set.
                    $title = $name.$alias;

                    $resources[] = [
                        'id' => Ulid::generate(),
                        'title' => $title,
                        'value' => $entry['mail'],
                    ];
                }

                usort($resources, fn ($a, $b) => strcmp((string) $a['title'], (string) $b['title']));

                return $resources;
            }
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
            'kobaHost' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
            'kobaApiKey' => [
                'type' => 'string',
            ],
        ];
    }

    public function getRequiredConfiguration(): array
    {
        return ['resources'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    /**
     * @return array
     *
     * @throws \Throwable
     */
    private function getBookingsFromResource(string $host, string $apikey, string $resource, string $group, int $from, int $to): array
    {
        $requestUrl = "$host/api/resources/$resource/group/$group/bookings/from/$from/to/$to";

        $response = $this->client->request('GET', $requestUrl, [
            'query' => [
                'apikey' => $apikey,
            ],
        ]);

        return $response->toArray();
    }

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'kobaHost' => [
                    'type' => 'string',
                ],
                'kobaApiKey' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['kobaHost', 'kobaApiKey'],
        ];
    }
}
