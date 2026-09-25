<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\RejseplanenFeedType;
use App\Service\FeedService;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

class RejseplanenFeedTypeTest extends KernelTestCase
{
    private const array LOCATION_NAME_RESPONSE = [
        'stopLocationOrCoordLocation' => [
            ['StopLocation' => ['extId' => '860005301', 'name' => 'Aarhus H']],
            ['CoordLocation' => ['name' => 'Aarhus Rådhus', 'lon' => 10.2, 'lat' => 56.15]],
            ['StopLocation' => ['extId' => '751434104', 'name' => 'Aarhus Rutebilstation']],
        ],
    ];

    private FeedService $feedService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->feedService = static::getContainer()->get(FeedService::class);
    }

    public function testSearchSendsKeyServerSideAndMapsStations(): void
    {
        $requestedUrls = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrls) {
            $requestedUrls[] = $url;

            return new MockResponse(json_encode(self::LOCATION_NAME_RESPONSE));
        });

        $stations = $this->createFeedType($client)->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'stations');

        $this->assertSame([
            ['id' => '860005301', 'name' => 'Aarhus H'],
            ['id' => '751434104', 'name' => 'Aarhus Rutebilstation'],
        ], $stations);

        $this->assertCount(1, $requestedUrls);
        $query = [];
        parse_str((string) parse_url($requestedUrls[0], PHP_URL_QUERY), $query);
        $this->assertStringStartsWith('https://www.rejseplanen.dk/api/location.name', $requestedUrls[0]);
        $this->assertSame('test-api-key', $query['accessId']);
        $this->assertSame('json', $query['format']);
        $this->assertSame('aarhus', $query['input']);
    }

    public function testRepeatedSearchIsCached(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(self::LOCATION_NAME_RESPONSE)),
        ]);
        $feedType = $this->createFeedType($client);

        $first = $feedType->getConfigOptions($this->searchRequest('Aarhus'), new FeedSource(), 'stations');
        $second = $feedType->getConfigOptions($this->searchRequest(' aarhus '), new FeedSource(), 'stations');

        $this->assertSame($first, $second);
        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testEmptySearchReturnsEmptyListWithoutRequest(): void
    {
        $client = new MockHttpClient([]);

        $stations = $this->createFeedType($client)->getConfigOptions($this->searchRequest(''), new FeedSource(), 'stations');

        $this->assertSame([], $stations);
        $this->assertSame(0, $client->getRequestsCount());
    }

    public function testUpstreamErrorReturnsNullAndIsNotCached(): void
    {
        $client = new MockHttpClient([
            new MockResponse('error', ['http_code' => 500]),
            new MockResponse(json_encode(self::LOCATION_NAME_RESPONSE)),
        ]);
        $feedType = $this->createFeedType($client);

        $this->assertNull($feedType->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'stations'));
        $this->assertCount(2, $feedType->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'stations'));
    }

    public function testTransportErrorReturnsNull(): void
    {
        $client = new MockHttpClient(function () {
            throw new TransportException('Connection refused');
        });

        $this->assertNull($this->createFeedType($client)->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'stations'));
    }

    public function testMissingApiKeyReturnsNullWithoutRequest(): void
    {
        $client = new MockHttpClient([]);

        $stations = $this->createFeedType($client, '')->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'stations');

        $this->assertNull($stations);
        $this->assertSame(0, $client->getRequestsCount());
    }

    public function testUnknownConfigNameReturnsNull(): void
    {
        $this->assertNull($this->createFeedType(new MockHttpClient([]))->getConfigOptions($this->searchRequest('aarhus'), new FeedSource(), 'unknown'));
    }

    public function testGetDataReturnsConfiguredStations(): void
    {
        $feed = new Feed();
        $feed->setConfiguration([
            'stations' => [
                ['id' => '860005301', 'name' => 'Aarhus H'],
                ['id' => 751434104, 'name' => 'Aarhus Rutebilstation'],
            ],
        ]);

        $this->assertSame([
            ['id' => '860005301', 'name' => 'Aarhus H'],
            ['id' => '751434104', 'name' => 'Aarhus Rutebilstation'],
        ], $this->createFeedType(new MockHttpClient([]))->getData($feed));
    }

    public function testGetDataRethrowsWhenStationsAreMissing(): void
    {
        $feed = new Feed();
        $feed->setConfiguration([]);

        $this->expectException(\RuntimeException::class);

        $this->createFeedType(new MockHttpClient([]))->getData($feed);
    }

    public function testAdminFormOptionsPointAtConfigEndpoint(): void
    {
        $feedSource = new FeedSource();
        $feedSource->setId(Ulid::fromString('01HZZZZZZZZZZZZZZZZZZZZZZZ'));

        $options = $this->createFeedType(new MockHttpClient([]))->getAdminFormOptions($feedSource);

        $this->assertCount(1, $options);
        $this->assertSame('station-selector', $options[0]['input']);
        $this->assertSame('stations', $options[0]['name']);
        $this->assertStringEndsWith('/v2/feed-sources/01HZZZZZZZZZZZZZZZZZZZZZZZ/config/stations', $options[0]['endpoint']);
    }

    public function testSupportsTravelOutput(): void
    {
        $feedType = $this->createFeedType(new MockHttpClient([]));

        $this->assertSame(FeedOutputModels::TRAVEL_OUTPUT, $feedType->getSupportedFeedOutputType());
        $this->assertSame([], $feedType->getRequiredSecrets());
        $this->assertSame(['stations'], $feedType->getRequiredConfiguration());
    }

    private function createFeedType(MockHttpClient $client, string $apiKey = 'test-api-key'): RejseplanenFeedType
    {
        return new RejseplanenFeedType($this->feedService, $client, new ArrayAdapter(), new NullLogger(), $apiKey);
    }

    private function searchRequest(string $search): Request
    {
        return new Request(['search' => $search]);
    }
}
