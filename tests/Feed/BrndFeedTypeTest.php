<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\BrndFeedType;
use App\Feed\SourceType\Brnd\ApiClient;
use App\Service\FeedService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class BrndFeedTypeTest extends TestCase
{
    /**
     * @dataProvider provideFilterCases
     *
     * @param array<string, string> $configuration
     * @param list<string> $expectedBookingCodes
     */
    public function testGetDataFiltersBookingsByAreaAndFacilityIds(
        array $configuration,
        string $apiVersion,
        array $expectedBookingCodes,
    ): void {
        $feedType = $this->createFeedType($this->sampleBookings());
        $feed = $this->createFeed($configuration, $apiVersion);

        $data = $feedType->getData($feed);
        $bookingCodes = array_column($data['bookings'], 'bookingcode');

        $this->assertSame($expectedBookingCodes, $bookingCodes);
    }

    /**
     * @return iterable<string, array{0: array<string, string>, 1: string, 2: list<string>}>
     */
    public function provideFilterCases(): iterable
    {
        yield 'single area ID' => [
            ['sport_center_id' => 'sport-1', 'area' => '42372'],
            '2.0',
            ['BKN-1'],
        ];

        yield 'multiple area IDs match any listed ID' => [
            ['sport_center_id' => 'sport-1', 'area' => '42372,42365'],
            '2.0',
            ['BKN-1', 'BKN-2'],
        ];

        yield 'multiple facility IDs match any listed ID' => [
            ['sport_center_id' => 'sport-1', 'facility' => '42375,42377'],
            '2.0',
            ['BKN-1', 'BKN-4'],
        ];

        yield 'area and facility filters are combined with AND' => [
            ['sport_center_id' => 'sport-1', 'area' => '42372,42365', 'facility' => '42367'],
            '2.0',
            ['BKN-2'],
        ];

        yield 'empty filters return all bookings' => [
            ['sport_center_id' => 'sport-1'],
            '2.0',
            ['BKN-1', 'BKN-2', 'BKN-3', 'BKN-4'],
        ];

        yield 'unknown ID returns no bookings' => [
            ['sport_center_id' => 'sport-1', 'area' => '99999'],
            '2.0',
            [],
        ];

        yield 'API v1.0 ignores ID filters' => [
            ['sport_center_id' => 'sport-1', 'area' => '42372'],
            '1.0',
            ['BKN-1', 'BKN-2', 'BKN-3', 'BKN-4'],
        ];
    }

    public function testGetAdminFormOptionsIncludesHelpTextForIdFiltersOnApiV2(): void
    {
        $feedType = $this->createFeedType([]);
        $feedSource = $this->createFeedSource('2.0');

        $options = $feedType->getAdminFormOptions($feedSource);
        $areaOption = $this->findFormOption($options, 'area');
        $facilityOption = $this->findFormOption($options, 'facility');

        $expectedHelpText = 'Flere ID\'er adskilles med komma uden mellemrum, f.eks. 42373,42374,42375.';

        $this->assertSame($expectedHelpText, $areaOption['helpText'] ?? null);
        $this->assertSame($expectedHelpText, $facilityOption['helpText'] ?? null);
    }

    public function testGetAdminFormOptionsHidesIdFiltersOnApiV1(): void
    {
        $feedType = $this->createFeedType([]);
        $feedSource = $this->createFeedSource('1.0');

        $options = $feedType->getAdminFormOptions($feedSource);

        $this->assertNull($this->findFormOption($options, 'area'));
        $this->assertNull($this->findFormOption($options, 'facility'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleBookings(): array
    {
        return [
            $this->booking('BKN-1', 42372, 42375),
            $this->booking('BKN-2', 42365, 42367),
            $this->booking('BKN-3', 42368, 42370),
            $this->booking('BKN-4', 42373, 42377),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function booking(string $code, int $areaId, int $facilityId): array
    {
        return [
            'ansøgning' => $code,
            'ansøgt_af' => 'Test booking',
            'områdeId' => $areaId,
            'facilitetsId' => $facilityId,
        ];
    }

    /**
     * @param list<array<string, mixed>> $bookings
     */
    private function createFeedType(array $bookings): BrndFeedType
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('getInfomonitorBookingsDetails')->willReturn($bookings);

        return new BrndFeedType(
            $this->createMock(FeedService::class),
            $apiClient,
            $this->createMock(CacheItemPoolInterface::class),
            $this->createMock(LoggerInterface::class),
        );
    }

    /**
     * @param array<string, string> $configuration
     */
    private function createFeed(array $configuration, string $apiVersion): Feed
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfiguration')->willReturn($configuration);
        $feed->method('getFeedSource')->willReturn($this->createFeedSource($apiVersion));

        return $feed;
    }

    private function createFeedSource(string $apiVersion): FeedSource
    {
        $feedSource = $this->createMock(FeedSource::class);
        $feedSource->method('getSecrets')->willReturn([
            'api_base_uri' => 'https://brndapi.brnd.com',
            'company_id' => 'company',
            'api_auth_key' => 'key',
            'api_version' => $apiVersion,
        ]);

        return $feedSource;
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
}
