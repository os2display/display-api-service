<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Entity\Tenant;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedConfigurationException;
use App\Feed\FeedOutputModels;
use App\Feed\MsGraphFeedType;
use App\Feed\OutputModel\Calendar\CalendarEvent;
use App\Feed\OutputModel\Calendar\Location;
use App\Feed\OutputModel\Calendar\Resource;
use App\Feed\SourceType\MsGraph\ApiClient;
use App\Feed\SourceType\MsGraph\MsGraphException;
use App\Feed\SourceType\MsGraph\TokenException;
use App\Service\FeedService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

class MsGraphFeedTypeTest extends TestCase
{
    private const string ROOM_1 = 'room1@example.com';
    private const string ROOM_2 = 'room2@example.com';

    public function testSupportedFeedOutputTypeIsCalendar(): void
    {
        $this->assertSame(FeedOutputModels::CALENDAR_OUTPUT, $this->createFeedType($this->createMock(ApiClient::class))->getSupportedFeedOutputType());
    }

    public function testGetDataReturnsCalendarOutputSortedByStartTime(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willReturn($this->sampleRooms());
        $apiClient->method('getEvents')->willReturnCallback(
            fn (FeedSource $feedSource, string $roomEmail): array => match ($roomEmail) {
                self::ROOM_1 => [new CalendarEvent('event-1', 'Møde A', 200, 300, self::ROOM_1, self::ROOM_1)],
                self::ROOM_2 => [new CalendarEvent('event-2', 'Møde B', 100, 150, self::ROOM_2, self::ROOM_2)],
                default => [],
            },
        );

        $data = $this->createFeedType($apiClient)->getData($this->createFeed(['resources' => [self::ROOM_1, self::ROOM_2]]));

        $this->assertSame([100, 200], array_column($data, 'startTime'));
        $this->assertSame(['Møde B', 'Møde A'], array_column($data, 'title'));
        $this->assertSame(['Lokale 2', 'Lokale 1'], array_column($data, 'resourceTitle'));
        $this->assertSame([self::ROOM_2, self::ROOM_1], array_column($data, 'resourceId'));
        $this->assertSame([150, 300], array_column($data, 'endTime'));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{26}$/', $data[0]['id']);
    }

    public function testGetDataFallsBackToEmailWhenRoomNamesCannotBeResolved(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willThrowException(new MsGraphException('Place.Read.All not granted'));
        $apiClient->method('getEvents')->willReturn([new CalendarEvent('event-1', 'Møde A', 200, 300, self::ROOM_1, self::ROOM_1)]);

        $data = $this->createFeedType($apiClient)->getData($this->createFeed(['resources' => [self::ROOM_1]]));

        $this->assertSame(self::ROOM_1, $data[0]['resourceTitle']);
    }

    public function testGetDataRequestsSevenDaysFromMidnightToday(): void
    {
        $capturedFrom = null;
        $capturedTo = null;

        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willReturn($this->sampleRooms());
        $apiClient->method('getEvents')->willReturnCallback(
            function (FeedSource $feedSource, string $roomEmail, \DateTimeImmutable $from, \DateTimeImmutable $to) use (&$capturedFrom, &$capturedTo): array {
                $capturedFrom = $from;
                $capturedTo = $to;

                return [];
            },
        );

        $this->createFeedType($apiClient)->getData($this->createFeed(['resources' => [self::ROOM_1]]));

        $expectedFrom = new \DateTimeImmutable('today midnight');

        $this->assertEquals($expectedFrom, $capturedFrom);
        $this->assertEquals($expectedFrom->add(new \DateInterval('P7D')), $capturedTo);
        $this->assertSame('00:00:00', $capturedTo?->format('H:i:s'));
    }

    public function testGetDataPassesPerFeedCacheTtlToApiClient(): void
    {
        $capturedTtl = null;

        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willReturn($this->sampleRooms());
        $apiClient->method('getEvents')->willReturnCallback(
            function (FeedSource $feedSource, string $roomEmail, \DateTimeImmutable $from, \DateTimeImmutable $to, int $cacheTtlSeconds) use (&$capturedTtl): array {
                $capturedTtl = $cacheTtlSeconds;

                return [];
            },
        );

        $this->createFeedType($apiClient)->getData($this->createFeed([
            'resources' => [self::ROOM_1],
            'cache_expire_minutes' => 30,
        ]));

        $this->assertSame(1800, $capturedTtl);
    }

    /**
     * @dataProvider provideCacheExpireCases
     *
     * @param array<string, mixed>|null $configuration
     */
    public function testGetCacheExpireSeconds(?array $configuration, int $expected): void
    {
        $this->assertSame($expected, MsGraphFeedType::getCacheExpireSeconds($configuration));
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>|null, 1: int}>
     */
    public function provideCacheExpireCases(): iterable
    {
        yield 'default when not configured' => [[], MsGraphFeedType::DEFAULT_CACHE_EXPIRE_MINUTES * 60];
        yield 'default when configuration is null' => [null, MsGraphFeedType::DEFAULT_CACHE_EXPIRE_MINUTES * 60];
        yield 'default when not numeric' => [['cache_expire_minutes' => 'abc'], MsGraphFeedType::DEFAULT_CACHE_EXPIRE_MINUTES * 60];
        yield 'numeric string from the admin form' => [['cache_expire_minutes' => '15'], 900];
        yield 'clamped to minimum' => [['cache_expire_minutes' => 0], MsGraphFeedType::MIN_CACHE_EXPIRE_MINUTES * 60];
        yield 'negative is clamped to minimum' => [['cache_expire_minutes' => -10], MsGraphFeedType::MIN_CACHE_EXPIRE_MINUTES * 60];
        yield 'clamped to maximum' => [['cache_expire_minutes' => 100000], MsGraphFeedType::MAX_CACHE_EXPIRE_MINUTES * 60];
    }

    /**
     * The error-cache contract: getData must rethrow so FeedService caches the
     * empty result for 30s instead of the full feed TTL.
     */
    public function testGetDataThrowsWhenResourcesAreNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MsGraphFeedType: Resources not set.');

        $this->createFeedType($this->createMock(ApiClient::class))->getData($this->createFeed([]));
    }

    public function testGetDataRethrowsApiClientFailures(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willReturn($this->sampleRooms());
        $apiClient->method('getEvents')->willThrowException(new MsGraphException('MS Graph request failed'));

        $this->expectException(MsGraphException::class);

        $this->createFeedType($apiClient)->getData($this->createFeed(['resources' => [self::ROOM_1]]));
    }

    public function testGetAdminFormOptionsExposesResourceSelectorAndCacheField(): void
    {
        $options = $this->createFeedType($this->createMock(ApiClient::class))->getAdminFormOptions($this->createFeedSource());

        $resourceSelector = $this->findFormOption($options, 'resources');
        $cacheField = $this->findFormOption($options, 'cache_expire_minutes');

        $this->assertSame('multiselect-from-endpoint', $resourceSelector['input'] ?? null);
        $this->assertSame('https://example.com/config/resources', $resourceSelector['endpoint'] ?? null);
        $this->assertSame('input', $cacheField['input'] ?? null);
        $this->assertSame('number', $cacheField['type'] ?? null);
        $this->assertSame(MsGraphFeedType::DEFAULT_CACHE_EXPIRE_MINUTES, $cacheField['defaultValue'] ?? null);
    }

    public function testGetConfigOptionsReturnsRoomsForResources(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willReturn($this->sampleRooms());

        $options = $this->createFeedType($apiClient)->getConfigOptions(new Request(), $this->createFeedSource(), 'resources');

        $this->assertSame(['Lokale 1 ('.self::ROOM_1.')', 'Lokale 2 ('.self::ROOM_2.')'], array_column($options ?? [], 'title'));
        $this->assertSame([self::ROOM_1, self::ROOM_2], array_column($options ?? [], 'value'));
    }

    public function testGetConfigOptionsReturnsRoomListsForLocations(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRoomLists')->willReturn([
            'building-b@example.com' => new Location('building-b@example.com', 'Bygning B'),
            'building-a@example.com' => new Location('building-a@example.com', 'Bygning A'),
        ]);

        $options = $this->createFeedType($apiClient)->getConfigOptions(new Request(), $this->createFeedSource(), 'locations');

        $this->assertSame(['Bygning A', 'Bygning B'], array_column($options ?? [], 'title'));
    }

    public function testGetConfigOptionsReturnsNullForUnknownName(): void
    {
        $this->assertNull($this->createFeedType($this->createMock(ApiClient::class))->getConfigOptions(new Request(), $this->createFeedSource(), 'unknown'));
    }

    /**
     * @dataProvider provideConfigurationFailureCases
     */
    public function testGetConfigOptionsReportsSafeErrors(int $status, string $expectedMessage, bool $tokenRequest = false): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willThrowException(
            $tokenRequest ? new TokenException('Sensitive provider response', $status) : new MsGraphException('Sensitive provider response', $status),
        );

        $this->expectException(FeedConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createFeedType($apiClient)->getConfigOptions(new Request(), $this->createFeedSource(), 'resources');
    }

    /**
     * @return iterable<string, array{int, string, 2?: bool}>
     */
    public function provideConfigurationFailureCases(): iterable
    {
        yield 'token request rejected' => [400, 'ms-graph-feed-type.errors.authentication', true];
        yield 'token request unauthorized' => [401, 'ms-graph-feed-type.errors.authentication', true];
        yield 'invalid Graph request' => [400, 'ms-graph-feed-type.errors.bad-request'];
        yield 'invalid Graph token' => [401, 'ms-graph-feed-type.errors.invalid-token'];
        yield 'forbidden' => [403, 'ms-graph-feed-type.errors.access-denied'];
        yield 'throttled' => [429, 'ms-graph-feed-type.errors.throttled'];
        yield 'connection failure' => [0, 'ms-graph-feed-type.errors.connection'];
        yield 'token connection failure' => [0, 'ms-graph-feed-type.errors.connection', true];
        yield 'token service unavailable' => [503, 'ms-graph-feed-type.errors.connection', true];
    }

    public function testTokenEndpointRejectionIsDistinguishedFromGraphRejection(): void
    {
        $client = new ApiClient(new MockHttpClient([
            new MockResponse('{"error":"invalid_client"}', ['http_code' => 400]),
        ]), new ArrayAdapter());

        $this->expectException(FeedConfigurationException::class);
        $this->expectExceptionMessage('ms-graph-feed-type.errors.authentication');

        $this->createFeedType($client)->getConfigOptions(new Request(), $this->createFeedSource(), 'locations');
    }

    public function testGraphBadRequestIsNotReportedAsInvalidCredentials(): void
    {
        $client = new ApiClient(new MockHttpClient([
            new MockResponse('{"access_token":"test-token","expires_in":3600}'),
            new MockResponse('{"error":{"code":"BadRequest"}}', ['http_code' => 400]),
        ]), new ArrayAdapter());

        $this->expectException(FeedConfigurationException::class);
        $this->expectExceptionMessage('ms-graph-feed-type.errors.bad-request');

        $this->createFeedType($client)->getConfigOptions(new Request(), $this->createFeedSource(), 'locations');
    }

    public function testRoomDiscoveryContinuesWithoutNextLink(): void
    {
        $rooms = array_map(static fn (int $number): array => [
            'emailAddress' => 'room'.$number.'@example.com',
            'displayName' => 'Room '.$number,
        ], range(1, 100));
        $requests = [];
        $http = new MockHttpClient(static function (string $method, string $url) use (&$requests, $rooms): MockResponse {
            $requests[] = $url;
            if ('POST' === $method) {
                return new MockResponse('{"access_token":"test-token","expires_in":3600}');
            }

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            self::assertSame('100', $query['$top']);

            return new MockResponse(json_encode(['value' => isset($query['$skip']) ? [
                ['emailAddress' => 'last@example.com', 'displayName' => 'Last room'],
            ] : $rooms], JSON_THROW_ON_ERROR));
        });

        $client = new ApiClient($http, new ArrayAdapter());
        $result = $client->getRooms($this->createFeedSource());

        $this->assertCount(101, $result);
        $this->assertArrayHasKey('last@example.com', $result);
        parse_str((string) parse_url($requests[2], PHP_URL_QUERY), $lastQuery);
        $this->assertSame('100', $lastQuery['$skip']);
    }

    public function testRoomDiscoveryFollowsNextLinkWithoutReplacingItsQuery(): void
    {
        $next = ApiClient::GRAPH_ENDPOINT.'/places/microsoft.graph.room?$skiptoken=cursor';
        $requests = [];
        $http = new MockHttpClient(static function (string $method, string $url) use (&$requests, $next): MockResponse {
            $requests[] = $url;
            if ('POST' === $method) {
                return new MockResponse('{"access_token":"test-token","expires_in":3600}');
            }

            return new MockResponse(json_encode([
                'value' => [['emailAddress' => $url === $next ? 'second@example.com' : 'first@example.com']],
                '@odata.nextLink' => $url === $next ? null : $next,
            ], JSON_THROW_ON_ERROR));
        });

        $rooms = (new ApiClient($http, new ArrayAdapter()))->getRooms($this->createFeedSource());

        $this->assertCount(2, $rooms);
        $this->assertSame($next, $requests[2]);
    }

    public function testRoomListMembersUseDocumentedEndpoint(): void
    {
        $http = new MockHttpClient(static function (string $method, string $url): MockResponse {
            if ('POST' === $method) {
                return new MockResponse('{"access_token":"test-token","expires_in":3600}');
            }

            self::assertSame('/v1.0/places/building%40example.com/microsoft.graph.roomlist/rooms', parse_url($url, PHP_URL_PATH));

            return new MockResponse('{"value":[{"emailAddress":"room@example.com","displayName":"Room"}]}');
        });

        $rooms = (new ApiClient($http, new ArrayAdapter()))->getRooms($this->createFeedSource(), 'building@example.com');

        $this->assertSame('building@example.com', $rooms['room@example.com']->locationId);
    }

    public function testRoomDiscoveryFailsRatherThanReturningTruncatedResults(): void
    {
        $http = new MockHttpClient(static fn (string $method): MockResponse => new MockResponse(
            'POST' === $method ? '{"access_token":"test-token","expires_in":3600}' : json_encode([
                'value' => array_fill(0, 100, ['emailAddress' => 'room@example.com']),
            ], JSON_THROW_ON_ERROR),
        ));

        $this->expectException(MsGraphException::class);
        $this->expectExceptionMessage('refusing to return an incomplete result');

        (new ApiClient($http, new ArrayAdapter()))->getRooms($this->createFeedSource());
    }

    public function testRoomListFailureDoesNotExposeProviderResponse(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRoomLists')->willThrowException(new MsGraphException('Sensitive provider response', 401));

        try {
            $this->createFeedType($apiClient)->getConfigOptions(new Request(), $this->createFeedSource(), 'locations');
            $this->fail('Expected a safe configuration error.');
        } catch (FeedConfigurationException $exception) {
            $this->assertSame('ms-graph-feed-type.errors.invalid-token', $exception->getMessage());
            $this->assertStringNotContainsString('Sensitive provider response', $exception->getMessage());
        }
    }

    public function testGetRequiredSecretsOnlyExposesNonSensitiveValues(): void
    {
        $secrets = $this->createFeedType($this->createMock(ApiClient::class))->getRequiredSecrets();

        $this->assertSame(['tenant_id', 'client_id', 'client_secret', 'locations'], array_keys($secrets));
        $this->assertArrayNotHasKey('exposeValue', $secrets['client_secret']);
        $this->assertArrayNotHasKey('exposeValue', $secrets['tenant_id']);
        $this->assertArrayNotHasKey('exposeValue', $secrets['client_id']);
        $this->assertTrue($secrets['locations']['exposeValue']);
    }

    /**
     * @param list<array<string, mixed>> $options
     *
     * @return array<string, mixed>|null
     */
    private function findFormOption(array $options, string $name): ?array
    {
        foreach ($options as $option) {
            if (($option['name'] ?? null) === $name) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return array<string, \App\Feed\OutputModel\Calendar\Resource>
     */
    private function sampleRooms(): array
    {
        return [
            self::ROOM_2 => new Resource(self::ROOM_2, self::ROOM_2, '', 'Lokale 2'),
            self::ROOM_1 => new Resource(self::ROOM_1, self::ROOM_1, '', 'Lokale 1'),
        ];
    }

    private function createFeedType(ApiClient $apiClient): MsGraphFeedType
    {
        $feedService = $this->createMock(FeedService::class);
        $feedService->method('getFeedSourceConfigUrl')->willReturnCallback(
            static fn (FeedSource $feedSource, string $name): string => 'https://example.com/config/'.$name,
        );

        return new MsGraphFeedType($feedService, $apiClient, $this->createMock(LoggerInterface::class));
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function createFeed(array $configuration): Feed
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfiguration')->willReturn($configuration);
        $feed->method('getFeedSource')->willReturn($this->createFeedSource());
        $feed->method('getTenant')->willReturn($this->createTenant());

        return $feed;
    }

    private function createFeedSource(): FeedSource
    {
        $feedSource = $this->createMock(FeedSource::class);
        $feedSource->method('getSecrets')->willReturn([
            'tenant_id' => 'contoso.onmicrosoft.com',
            'client_id' => '11111111-2222-3333-4444-555555555555',
            'client_secret' => 'secret',
        ]);
        $feedSource->method('getTenant')->willReturn($this->createTenant());

        return $feedSource;
    }

    private function createTenant(): Tenant
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('getTenantKey')->willReturn('test');

        return $tenant;
    }
}
