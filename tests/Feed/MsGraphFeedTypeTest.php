<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Entity\Tenant;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\MsGraphFeedType;
use App\Feed\OutputModel\Calendar\CalendarEvent;
use App\Feed\OutputModel\Calendar\Location;
use App\Feed\OutputModel\Calendar\Resource;
use App\Feed\SourceType\MsGraph\ApiClient;
use App\Feed\SourceType\MsGraph\MsGraphException;
use App\Service\FeedService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
     * The admin form polls this while the editor is still typing credentials,
     * so a Graph failure must degrade instead of erroring the form.
     */
    public function testGetConfigOptionsSwallowsExceptions(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getRooms')->willThrowException(new MsGraphException('invalid_client'));

        $this->assertNull($this->createFeedType($apiClient)->getConfigOptions(new Request(), $this->createFeedSource(), 'resources'));
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
