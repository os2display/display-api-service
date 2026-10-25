<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Calendar\CalendarEvent;
use App\Feed\OutputModel\Calendar\Location;
use App\Feed\OutputModel\Calendar\Resource;
use App\Feed\SourceType\MsGraph\ApiClient;
use App\Feed\SourceType\MsGraph\SecretsDTO;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

/**
 * Supplies 'calendar' data from Microsoft Graph room (resource) mailboxes.
 *
 * Mirrors the structure of {@see CalendarApiFeedType}:
 * - The feed source holds the Entra ID app credentials and, optionally, a set
 *   of *locations* (Graph room lists) that narrow the room selection.
 * - Each feed selects the *resources* (rooms) it shows, from a dropdown that is
 *   populated live from Graph.
 * - `getData()` returns the events of those rooms for a seven day window.
 *
 * The window is midnight today through midnight seven days later, i.e. today
 * plus the following six calendar days, computed in the application time zone.
 *
 * @see https://learn.microsoft.com/en-us/graph/api/resources/place
 * @see https://learn.microsoft.com/en-us/graph/api/calendar-list-calendarview
 */
class MsGraphFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::CALENDAR_OUTPUT;

    public const int WINDOW_DAYS = 7;
    public const int DEFAULT_CACHE_EXPIRE_MINUTES = 5;
    public const int MIN_CACHE_EXPIRE_MINUTES = 1;
    public const int MAX_CACHE_EXPIRE_MINUTES = 1440;

    /**
     * Room mailboxes are commonly configured to strip the meeting subject
     * (`Set-CalendarProcessing -DeleteSubject $true`), which leaves the event
     * without a title. Show the room as booked rather than an empty line.
     */
    public const string DEFAULT_EVENT_TITLE = 'Optaget';

    public function __construct(
        private readonly FeedService $feedService,
        private readonly ApiClient $apiClient,
        private readonly LoggerInterface $feedLogger,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @return list<array{id: string, title: string, startTime: int, endTime: int, resourceTitle: string, resourceId: string}>
     */
    public function getData(Feed $feed): array
    {
        $configuration = $feed->getConfiguration();

        try {
            $feedSource = $feed->getFeedSource();

            if (null === $feedSource) {
                throw new \RuntimeException('MsGraphFeedType: Feed source not set.');
            }

            $requestedResources = $configuration['resources'] ?? null;

            if (!is_array($requestedResources) || 0 === count($requestedResources)) {
                throw new \RuntimeException('MsGraphFeedType: Resources not set.');
            }

            $cacheTtlSeconds = self::getCacheExpireSeconds($configuration);
            [$from, $to] = $this->getTimeWindow();

            $resourceTitles = $this->getResourceTitles($feedSource);

            $events = [];

            foreach ($requestedResources as $requestedResource) {
                if (!is_string($requestedResource) || '' === $requestedResource) {
                    continue;
                }

                $events = array_merge($events, $this->apiClient->getEvents(
                    $feedSource,
                    $requestedResource,
                    $from,
                    $to,
                    $cacheTtlSeconds,
                    self::DEFAULT_EVENT_TITLE,
                ));
            }

            $resultsAsArray = array_map(static fn (CalendarEvent $event): array => [
                'id' => Ulid::generate(),
                'title' => $event->title,
                'startTime' => $event->startTimeTimestamp,
                'endTime' => $event->endTimeTimestamp,
                'resourceTitle' => $resourceTitles[$event->resourceId] ?? $event->resourceDisplayName,
                'resourceId' => $event->resourceId,
            ], $events);

            usort($resultsAsArray, static fn (array $a, array $b): int => $a['startTime'] <=> $b['startTime']);

            return $resultsAsArray;
        } catch (\Throwable $throwable) {
            $this->feedLogger->error('MsGraphFeedType: Failed to get data for feed {feedId} (tenant {tenantKey}): {message}', [
                'feedId' => $feed->getId(),
                'tenantKey' => $feed->getTenant()->getTenantKey(),
                'resources' => $configuration['resources'] ?? [],
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return list<array<string, mixed>>
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        return [
            [
                'key' => 'ms-graph-resource-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $this->feedService->getFeedSourceConfigUrl($feedSource, 'resources'),
                'name' => 'resources',
                'label' => 'Vælg lokaler',
                'helpText' => 'Her vælger du hvilke lokaler, der skal hentes møder fra.',
                'formGroupClasses' => 'mb-3',
            ],
            [
                'key' => 'ms-graph-cache-expire-minutes',
                'input' => 'input',
                'type' => 'number',
                'name' => 'cache_expire_minutes',
                'label' => 'Cachetid (minutter)',
                'helpText' => sprintf(
                    'Hvor længe kalenderdata må genbruges, før de hentes fra Microsoft Graph igen. Mellem %d og %d minutter. Standard er %d minutter.',
                    self::MIN_CACHE_EXPIRE_MINUTES,
                    self::MAX_CACHE_EXPIRE_MINUTES,
                    self::DEFAULT_CACHE_EXPIRE_MINUTES,
                ),
                'defaultValue' => self::DEFAULT_CACHE_EXPIRE_MINUTES,
                'formGroupClasses' => 'mb-3',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return list<array{id: string, title: string, value: string}>|null
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('locations' === $name) {
                $locationOptions = array_map(static fn (Location $location): array => [
                    'id' => Ulid::generate(),
                    'title' => $location->displayName,
                    'value' => $location->id,
                ], array_values($this->apiClient->getRoomLists($feedSource)));

                usort($locationOptions, static fn (array $a, array $b): int => strcmp((string) $a['title'], (string) $b['title']));

                return $locationOptions;
            }

            if ('resources' === $name) {
                $resourceOptions = array_map(static fn (Resource $resource): array => [
                    'id' => Ulid::generate(),
                    'title' => $resource->displayName.' ('.$resource->id.')',
                    'value' => $resource->id,
                ], array_values($this->getRooms($feedSource)));

                usort($resourceOptions, static fn (array $a, array $b): int => strcmp((string) $a['title'], (string) $b['title']));

                return $resourceOptions;
            }
        } catch (\Throwable $throwable) {
            $this->feedLogger->error('MsGraphFeedType: Failed to get config "{name}" for feed source {feedSourceId} (tenant {tenantKey}): {message}', [
                'name' => $name,
                'feedSourceId' => $feedSource->getId(),
                'tenantKey' => $feedSource->getTenant()->getTenantKey(),
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            // Never expose provider responses, which may contain credentials.
            $message = match ((int) $throwable->getCode()) {
                400, 401 => 'Microsoft Graph afviste forbindelsen. Kontrollér Tenant ID, Client ID og Client Secret samt application-rettigheden Place.Read.All med administratorsamtykke. Efter ændring af rettigheder kan et cachet token være gyldigt i op til en time.',
                403 => 'Microsoft Graph nægtede adgang til lokalerne. Kontrollér application-rettigheden Place.Read.All og administratorsamtykke.',
                429 => 'Microsoft Graph modtager for mange forespørgsler. Vent lidt, og prøv igen.',
                default => 'Forbindelsen til Microsoft Graph kunne ikke oprettes. Kontrollér de gemte legitimationsoplysninger, eller kontakt en administrator.',
            };

            throw new FeedConfigurationException($message, 0, $throwable);
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getRequiredSecrets(): array
    {
        return [
            'tenant_id' => ['type' => 'string'],
            'client_id' => ['type' => 'string'],
            'client_secret' => ['type' => 'string'],
            'locations' => [
                'type' => 'string_array',
                'exposeValue' => true,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function getRequiredConfiguration(): array
    {
        return ['resources'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'tenant_id' => ['type' => 'string', 'minLength' => 1],
                'client_id' => ['type' => 'string', 'minLength' => 1],
                'client_secret' => ['type' => 'string', 'minLength' => 1],
                'locations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['tenant_id', 'client_id', 'client_secret'],
        ];
    }

    /**
     * Translate the per-feed cache setting (minutes) into seconds.
     *
     * @param array<string, mixed>|null $configuration
     */
    public static function getCacheExpireSeconds(?array $configuration): int
    {
        $minutes = $configuration['cache_expire_minutes'] ?? null;

        if (!is_numeric($minutes)) {
            $minutes = self::DEFAULT_CACHE_EXPIRE_MINUTES;
        }

        $minutes = (int) $minutes;
        $minutes = max(self::MIN_CACHE_EXPIRE_MINUTES, min(self::MAX_CACHE_EXPIRE_MINUTES, $minutes));

        return $minutes * 60;
    }

    /**
     * Midnight today through midnight WINDOW_DAYS later, in the application time zone.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function getTimeWindow(): array
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());

        $from = new \DateTimeImmutable('today midnight', $timezone);
        $to = $from->add(new \DateInterval('P'.self::WINDOW_DAYS.'D'));

        return [$from, $to];
    }

    /**
     * Rooms available to the feed source: those of the selected room lists, or
     * every room in the tenant when no room list is selected.
     *
     * @return array<string, OutputModel\Calendar\Resource> keyed by room email address
     */
    private function getRooms(FeedSource $feedSource): array
    {
        $secrets = new SecretsDTO($feedSource);

        if ([] === $secrets->locations) {
            return $this->apiClient->getRooms($feedSource);
        }

        $rooms = [];

        foreach ($secrets->locations as $location) {
            $rooms = array_merge($rooms, $this->apiClient->getRooms($feedSource, $location));
        }

        return $rooms;
    }

    /**
     * Friendly room names keyed by room email address.
     *
     * Resolving names needs `Place.Read.All`, which the events themselves do
     * not. A missing/denied place lookup must therefore not take the feed down
     * — fall back to the email address as the title.
     *
     * @return array<string, string>
     */
    private function getResourceTitles(FeedSource $feedSource): array
    {
        try {
            $titles = [];

            foreach ($this->getRooms($feedSource) as $email => $resource) {
                $titles[$email] = $resource->displayName;
            }

            return $titles;
        } catch (\Throwable $throwable) {
            $this->feedLogger->warning('MsGraphFeedType: Could not resolve room names for feed source {feedSourceId}, falling back to email addresses: {message}', [
                'feedSourceId' => $feedSource->getId(),
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            return [];
        }
    }
}
