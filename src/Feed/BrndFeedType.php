<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\SourceType\Brnd\ApiClient;
use App\Feed\SourceType\Brnd\SecretsDTO;
use App\Service\FeedService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

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
                'label' => 'Sportcenter ID',
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

        try {
            $configuration = $feed->getConfiguration();
            $feedSource = $feed->getFeedSource();

            if (null == $feedSource) {
                return $result;
            }

            $secrets = new SecretsDTO($feedSource);

            $baseUri = $secrets->apiBaseUri;
            $sportCenterId = $configuration['sport_center_id'] ?? null;

            if ('' === $baseUri || null === $sportCenterId || '' === $sportCenterId) {
                return $result;
            }

            $bookings = $this->apiClient->getInfomonitorBookingsDetails($feedSource, $sportCenterId);

            $result['bookings'] = array_reduce($bookings, function (array $carry, array $booking): array {
                $parsedBooking = $this->parseBrndBooking($booking);

                // Validate that booking has required fields
                if (!empty($parsedBooking['bookingcode']) && !empty($parsedBooking['bookingBy'])) {
                    $carry[] = $parsedBooking;
                }

                return $carry;
            }, []);
        } catch (\Throwable) {
            // Silently catch all exceptions and return empty result
            // $result is already initialized with empty bookings array
        }

        return $result;
    }

    private function parseBrndBooking(array $booking): array
    {
        // Parse start time
        $startDateTime = null;
        if (!empty($booking['dato']) && isset($booking['starttid']) && is_string($booking['starttid'])) {
            try {
                // Trim starttid to 6 digits after dot for microseconds
                $starttid = preg_replace('/\.(\d{6})\d+$/', '.$1', $booking['starttid']);
                $dateOnly = substr($booking['dato'], 0, 10);
                $dateTimeString = $dateOnly.' '.$starttid;
                $startDateTime = \DateTimeImmutable::createFromFormat('m/d/Y H:i:s.u', $dateTimeString);
                if (false === $startDateTime) {
                    $startDateTime = null;
                }
            } catch (\ValueError) {
                $startDateTime = null;
            }
        }

        // Parse end time
        $endDateTime = null;
        if (!empty($booking['dato']) && isset($booking['sluttid']) && is_string($booking['sluttid'])) {
            try {
                $sluttid = preg_replace('/\.(\d{6})\d+$/', '.$1', $booking['sluttid']);
                $dateOnly = substr($booking['dato'], 0, 10);
                $dateTimeString = $dateOnly.' '.$sluttid;
                $endDateTime = \DateTimeImmutable::createFromFormat('m/d/Y H:i:s.u', $dateTimeString);
                if (false === $endDateTime) {
                    $endDateTime = null;
                }
            } catch (\ValueError) {
                $endDateTime = null;
            }
        }

        return [
            'bookingcode' => $booking['ansøgning'] ?? '',
            'remarks' => $booking['bemærkninger'] ?? '',
            'startTime' => $startDateTime ? $startDateTime->getTimestamp() : null,
            'endTime' => $endDateTime ? $endDateTime->getTimestamp() : null,
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
                'exposeValue' => false,
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
