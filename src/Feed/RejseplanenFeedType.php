<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Travel\Station;
use App\Feed\OutputModel\Travel\TravelOutput;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Supplies 'travel' data: the stations selected for a slide.
 *
 * The station search runs server-side so the Rejseplanen API key never reaches the browser.
 *
 * @see https://labs.rejseplanen.dk/
 */
class RejseplanenFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::TRAVEL_OUTPUT;

    private const string LOCATION_NAME_URL = 'https://www.rejseplanen.dk/api/location.name';
    private const int SEARCH_CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $rejseplanenCache,
        private readonly LoggerInterface $feedLogger,
        private readonly string $apiKey,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @return list<Station>
     */
    public function getData(Feed $feed): array
    {
        try {
            $stations = $feed->getConfiguration()['stations'] ?? null;

            if (!is_array($stations)) {
                throw new \RuntimeException('RejseplanenFeedType: Stations configuration is not set.');
            }

            $result = [];
            foreach ($stations as $station) {
                $id = $station['id'] ?? null;
                $name = $station['name'] ?? null;

                // Leave out entries that cannot be shown or used in the departure board URL.
                if (!is_scalar($id) || !is_scalar($name) || '' === (string) $id || '' === (string) $name) {
                    continue;
                }

                $result[] = new Station((string) $id, (string) $name);
            }

            return new TravelOutput($result)->toArray();
        } catch (\Throwable $throwable) {
            $this->feedLogger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return list<array<string, string>>
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'stations');

        // @TODO: Translation.
        return [
            [
                'key' => 'rejseplanen-station-selector',
                'input' => 'station-selector',
                'endpoint' => $endpoint,
                'name' => 'stations',
                'label' => 'Vælg stoppested',
                'helpText' => 'Hvis du er i tvivl om hvilke stoppesteder, henviser vi til din lokale busservice',
                'formGroupClasses' => 'mb-3',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return list<Station>|null
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        if ('stations' !== $name) {
            return null;
        }

        try {
            $search = trim((string) $request->query->get('search', ''));

            // The Rejseplanen API does not accept an empty search.
            if ('' === $search) {
                return [];
            }

            if ('' === $this->apiKey) {
                throw new \RuntimeException('RejseplanenFeedType: ADMIN_REJSEPLANEN_APIKEY is not set.');
            }

            // Entries hold Station objects; the prefix keeps them apart from
            // earlier entries that held plain arrays.
            $cacheKey = 'stations-'.sha1(mb_strtolower($search));

            // A failed request throws out of the callback, so errors are not cached.
            return $this->rejseplanenCache->get($cacheKey, function (ItemInterface $item) use ($search): array {
                $item->expiresAfter(self::SEARCH_CACHE_TTL_SECONDS);

                return $this->searchStations($search);
            });
        } catch (\Throwable $throwable) {
            $this->feedLogger->error('Rejseplanen station search failed: {code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
                'search' => $request->query->get('search'),
            ]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getRequiredSecrets(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @return list<string>
     */
    public function getRequiredConfiguration(): array
    {
        return ['stations'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, string>
     */
    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
        ];
    }

    /**
     * @return list<Station>
     */
    private function searchStations(string $search): array
    {
        // The key goes in a header, not the query: HTTP client exception
        // messages include the full URL, and those messages reach the logs
        // unredacted.
        $response = $this->client->request(Request::METHOD_GET, self::LOCATION_NAME_URL, [
            'auth_bearer' => $this->apiKey,
            'query' => [
                'format' => 'json',
                'input' => $search,
            ],
            // Symfony drops the Authorization header on a cross-host redirect,
            // so a redirect would silently turn into an unauthenticated request.
            'max_redirects' => 0,
        ]);

        $locations = $response->toArray()['stopLocationOrCoordLocation'] ?? [];

        $stations = [];
        foreach ($locations as $location) {
            $stopLocation = $location['StopLocation'] ?? null;

            if (!isset($stopLocation['extId'], $stopLocation['name'])) {
                continue;
            }

            $stations[] = new Station((string) $stopLocation['extId'], (string) $stopLocation['name']);
        }

        return new TravelOutput($stations)->toArray();
    }
}
