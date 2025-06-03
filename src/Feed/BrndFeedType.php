<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\ConfigOption;
use App\Feed\SourceType\Brnd\ApiClient;
use App\Feed\SourceType\Brnd\SecretsDTO;
use App\Service\FeedService;
use FeedIo\Feed\Item;
use FeedIo\Feed\Node\Category;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

/**
 * Brnd Bookingsystem Feed.
 *
 * @see https://brndapi.brnd.com/swagger/index.html
 */
class BrndFeedType implements FeedTypeInterface
{
    public const int CACHE_TTL = 3600;

    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::BRND_BOOKING_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly ApiClient $apiClient,
        private readonly CacheItemPoolInterface $feedsCache,
    ) {}

    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $feedEntryRecipients = $this->feedService->getFeedSourceConfigUrl($feedSource, 'sport-center');

        return [
            [
                'key' => 'brnd-sport-center-id',
                'input' => 'input',
                'type' => 'text',
                'name' => 'sport_center_id',
                'label' => 'Sport Center ID',
                'formGroupClasses' => 'mb-3',
            ],
        ];
    }

    public function getData(Feed $feed): array
    {
        $result = [
            'title' => 'BRND Booking',
            'bookings' => [],
        ];

        $configuration = $feed->getConfiguration();
        $feedSource = $feed->getFeedSource();

        if (null == $feedSource) {
            return $result;
        }

        $secrets = new SecretsDTO($feedSource);

        $baseUri = $secrets->apiBaseUri;
        $sportCenterId = $configuration['sport_center_id'] ?? null;

        if (empty($baseUri) || empty($sportCenterId)) {
            return $result;
        }

        $feedSource = $feed->getFeedSource();

        if (null === $feedSource) {
            return $result;
        }

        $bookings = $this->apiClient->getInfomonitorBookingsDetails($feedSource, $sportCenterId);
        
        $result['bookings'] = array_map([$this, 'parseBrndBooking'], $bookings);

        return $result;
    }

    private function parseBrndBooking(array $booking): array
    {
        return [
            'bookingcode' => $booking['ansøgning'] ?? '',
            'remarks' => $booking['bemærkninger'] ?? '',
            'date' => $booking['dato'] ?? '',
            'start' => $booking['starttid'] ?? '',
            'end' => $booking['sluttid'] ?? '',
            'complex' => $booking['anlæg'] ?? '',
            'area' => $booking['område'] ?? '',
            'facility' => $booking['facilitet'] ?? '',
            'activity' => $booking['aktivitet'] ?? '',
            'team' => $booking['hold'] ?? '',
            'status' => $booking['status'] ?? '',
            'checkIn' => $booking['checK_IN'] ?? '',
            'bookingBy' => $booking['ansøgt_af'] ?? '',
            'changingRooms' => $booking['omklædningsrum'] ?? '',
        ];
    }

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        return null;
    }

    public function getRequiredSecrets(): array
    {
        return [
            'api_base_uri' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
            'company_id' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
            'api_auth_key' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
        ];
    }

    public function getRequiredConfiguration(): array
    {
        return ['sport_center_id'];
    }

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
                'api_base_uri' => [
                    'type' => 'string',
                    'format' => 'uri',
                ],
                'company_id' => [
                    'type' => 'string',
                ],
                'api_auth_key' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['api_base_uri', 'company_id', 'api_auth_key'],
        ];
    }

    public static function getIdKey(FeedSource $feedSource): string
    {
        $ulid = $feedSource->getId();
        assert(null !== $ulid);

        return $ulid->toBase32();
    }
}


