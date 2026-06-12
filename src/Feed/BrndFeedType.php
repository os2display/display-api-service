<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\SourceType\Brnd\ApiClient;
use App\Feed\SourceType\Brnd\SecretsDTO;
use App\Service\FeedService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
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

    /**
     * BRND api datetime values are always given as 'Europe/Copenhagen'.
     */
    private const string BRND_API_TIMEZONE = 'Europe/Copenhagen';

    private const string BRND_API_VERSION_WITH_ID_FILTERING = '2.0';

    public function __construct(
        private readonly FeedService $feedService,
        private readonly ApiClient $apiClient,
        private readonly CacheItemPoolInterface $feedsCache,
        private readonly LoggerInterface $logger,
    ) {}

    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $feedEntryRecipients = $this->feedService->getFeedSourceConfigUrl($feedSource, 'sport-center');

        $options = [
            [
                'key' => 'brnd-sport-center-id',
                'input' => 'input',
                'type' => 'text',
                'name' => 'sport_center_id',
                'label' => 'Sportcenter ID',
                'formGroupClasses' => 'mb-3',
            ],
        ];

        if ($this->supportsIdFiltering($feedSource)) {
            $options[] = [
                'key' => 'brnd-area',
                'input' => 'input',
                'type' => 'text',
                'name' => 'area',
                'label' => 'Område ID',
                'formGroupClasses' => 'mb-3',
            ];
            $options[] = [
                'key' => 'brnd-facility',
                'input' => 'input',
                'type' => 'text',
                'name' => 'facility',
                'label' => 'Facilitet ID',
                'formGroupClasses' => 'mb-3',
            ];
        }

        return $options;
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
            $areaFilter = $configuration['area'] ?? '';
            $facilityFilter = $configuration['facility'] ?? '';

            if ('' === $baseUri || !is_string($sportCenterId) || '' === trim($sportCenterId)) {
                return $result;
            }

            $supportsIdFiltering = $this->supportsIdFiltering($feedSource);
            $areaFilterNormalized = self::normalizeFilterValue($areaFilter);
            $facilityFilterNormalized = self::normalizeFilterValue($facilityFilter);

            $bookings = $this->apiClient->getInfomonitorBookingsDetails($feedSource, $sportCenterId);

            $result['bookings'] = array_reduce($bookings, function (array $carry, mixed $booking) use ($areaFilterNormalized, $facilityFilterNormalized, $supportsIdFiltering): array {
                if (!is_array($booking)) {
                    return $carry;
                }

                $parsedBooking = $this->parseBrndBooking($booking);

                // Bail out if required fields are missing.
                if (empty($parsedBooking['bookingcode']) || empty($parsedBooking['bookingBy'])) {
                    return $carry;
                }

                // Bail out if area filter applies and booking area ID does not match.
                if ($supportsIdFiltering && '' !== $areaFilterNormalized) {
                    $bookingAreaId = self::normalizeFilterValue($parsedBooking['areaId'] ?? '');
                    if ($bookingAreaId !== $areaFilterNormalized) {
                        return $carry;
                    }
                }

                // Bail out if facility filter applies and booking facility ID does not match.
                if ($supportsIdFiltering && '' !== $facilityFilterNormalized) {
                    $bookingFacilityId = self::normalizeFilterValue($parsedBooking['facilityId'] ?? '');
                    if ($bookingFacilityId !== $facilityFilterNormalized) {
                        return $carry;
                    }
                }

                $carry[] = $parsedBooking;

                return $carry;
            }, []);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            throw $throwable;
        }

        return $result;
    }

    private static function normalizeFilterValue(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function parseBrndBooking(array $booking): array
    {
        $tz = new \DateTimeZone(self::BRND_API_TIMEZONE);
        // Parse start time
        $startDateTime = null;
        if (!empty($booking['dato']) && isset($booking['starttid']) && is_string($booking['starttid'])) {
            try {
                // Trim starttime to 6 digits after dot for microseconds
                $startTimeString = preg_replace('/\.(\d{6})\d+$/', '.$1', $booking['starttid']);
                $dateOnly = substr((string) $booking['dato'], 0, 10);
                $dateTimeString = $dateOnly.' '.$startTimeString;
                $startDateTime = \DateTimeImmutable::createFromFormat('m/d/Y H:i:s.u', $dateTimeString, $tz);
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
                $endTimeString = preg_replace('/\.(\d{6})\d+$/', '.$1', $booking['sluttid']);
                $dateOnly = substr((string) $booking['dato'], 0, 10);
                $dateTimeString = $dateOnly.' '.$endTimeString;
                $endDateTime = \DateTimeImmutable::createFromFormat('m/d/Y H:i:s.u', $dateTimeString, $tz);
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
            'areaId' => $booking['områdeId'] ?? '',
            'facilityId' => $booking['facilitetsId'] ?? '',
            'activity' => $booking['aktivitet'] ?? '',
            'team' => $booking['hold'] ?? '',
            'status' => $booking['status'] ?? '',
            'checkIn' => $booking['checK_IN'] ?? '',
            'bookingBy' => $booking['ansøgt_af'] ?? '',
            'changingRooms' => $booking['omklædningsrum'] ?? '',
        ];
    }

    private function supportsIdFiltering(FeedSource $feedSource): bool
    {
        $secrets = $feedSource->getSecrets();

        if (!is_array($secrets)) {
            return false;
        }

        return self::BRND_API_VERSION_WITH_ID_FILTERING === ($secrets['api_version'] ?? '1.0');
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
            'api_version' => [
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
                'api_version' => [
                    'type' => 'string',
                    'pattern' => '^\d+(\.\d+)?$',
                    'default' => '1.0',
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
