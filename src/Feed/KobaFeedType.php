<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KobaFeedType implements FeedTypeInterface
{
    public function __construct(private FeedService $feedService, private HttpClientInterface $client)
    {
    }

    public function getData(Feed $feed): ?array
    {
        $feedSource = $feed->getFeedSource();
        $secrets = $feedSource->getSecrets();
        $configuration = $this->feedService->getFeedConfiguration($feed);

        if (!isset($secrets['kobaHost']) || !isset($secrets['kobaApiKey'])) {
            return [];
        }

        $kobaHost = $secrets['kobaHost'];
        $kobaApiKey = $secrets['kobaApiKey'];
        $kobaGroup = $secrets['kobaGroup'] ?? 'default';

        if (!isset($configuration['resources'])) {
            return [];
        }

        $resources = $configuration['resources'];

        $now = time();

        // Round down to the nearest hour.
        $from = time() - ($now % 3600);

        // Get bookings for the coming week.
        // @TODO: Support for configuring interest period.
        $to = $from + 7 * 24 * 60 * 60;

        $results = [];

        foreach ($resources as $resource) {
            $requestUrl = "$kobaHost/api/resources/$resource/group/$kobaGroup/bookings/from/$from/to/$to";

            $response = $this->client->request('GET', $requestUrl, [
                'query' => [
                    'apikey' => $kobaApiKey,
                ],
            ]);

            $bookings = $response->toArray();

            foreach ($bookings as $booking) {
                $results[] = [
                    'title' => $booking['event_name'] ?? '',
                    'description' => $booking['event_description'] ?? '',
                    'startTime' => $booking['start_time'] ?? '',
                    'endTime' => $booking['end_time'] ?? '',
                    'resourceTitle' => $booking['resource_alias'] ?? '',
                    'resourceId' => $booking['resource_id'] ?? '',
                ];
            }
        }

        // Sort bookings by start time.
        usort($results, function ($a, $b) {
            return strcmp($a['startTime'], $b['startTime']);
        });

        return $results;
    }

    public function getAdmin(FeedSource $feedSource): ?array
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
        ];
    }

    public function getConfigOptions(FeedSource $feedSource, string $name): ?array
    {
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
                $resources[] = [
                    'id' => Ulid::generate(),
                    'title' => $entry['alias'] ?? $entry['name'] ?? $entry['mail'],
                    'value' => $entry['mail'],
                ];
            }

            return $resources;
        }

        return null;
    }
}
