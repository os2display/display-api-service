<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Calendar\CalendarEvent;
use App\Feed\OutputModel\Calendar\Location;
use App\Feed\OutputModel\Calendar\Resource;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Supplies 'calendar' data based on 3 endpoints:
 * - Locations
 * - Resources
 * - Events
 *
 * Select the locations for the feed source.
 * Resources that belong to the locations are selectable when creating a feed.
 * Events for the selected resources are returned from getData.
 */
class CalendarApiFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::CALENDAR_OUTPUT;
    final public const string EXCLUDE_IF_TITLE_NOT_CONTAINS = 'EXCLUDE_IF_TITLE_NOT_CONTAINS';
    final public const string REPLACE_TITLE_IF_CONTAINS = 'REPLACE_TITLE_IF_CONTAINS';

    private const string CACHE_KEY_LOCATIONS = 'locations';
    private const string CACHE_KEY_RESOURCES = 'resources';
    private const string CACHE_KEY_EVENTS = 'events';

    private array $mappings;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $calendarApiCache,
        private readonly string $locationEndpoint,
        private readonly string $resourceEndpoint,
        private readonly string $eventEndpoint,
        private readonly array $customMappings,
        private readonly array $eventModifiers,
        private readonly string $dateFormat,
        private readonly string $timezone,
        private readonly int $cacheExpireSeconds,
    ) {
        $this->mappings = $this->createMappings($this->customMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getData(Feed $feed): array
    {
        try {
            $configuration = $feed->getConfiguration();

            $enabledModifiers = $configuration['enabledModifiers'] ?? [];

            if (!isset($configuration['resources'])) {
                throw new \RuntimeException('CalendarApiFeedType: Resources not set.');
            }

            $requestedResources = $configuration['resources'];

            $allResources = $this->loadResources();

            $events = [];

            foreach ($requestedResources as $requestedResource) {
                $events = array_merge($events, $this->getResourceEvents($requestedResource));
            }

            $modifiedResults = static::applyModifiersToEvents($events, $this->eventModifiers, $enabledModifiers);

            $resultsAsArray = array_map(function (CalendarEvent $event) use ($allResources) {
                $resourceDisplayName = $event->resourceDisplayName;

                // Override resource title with resource display name from resources list.
                if (isset($allResources[$event->resourceId])) {
                    $resourceDisplayName = $allResources[$event->resourceId]->displayName;
                }

                return [
                    'id' => Ulid::generate(),
                    'title' => $event->title,
                    'startTime' => $event->startTimeTimestamp,
                    'endTime' => $event->endTimeTimestamp,
                    'resourceTitle' => $resourceDisplayName,
                    'resourceId' => $event->resourceId,
                ];
            }, $modifiedResults);

            // Sort bookings by start time.
            usort($resultsAsArray, fn (array $a, array $b) => $a['startTime'] > $b['startTime'] ? 1 : -1);

            return $resultsAsArray;
        } catch (\Throwable $throwable) {
            $this->logger->error('CalendarApiFeedType: Failed to get data for feed {feedId} (tenant {tenantKey}): {message}', [
                'feedId' => $feed->getId(),
                'tenantKey' => $feed->getTenant()->getTenantKey(),
                'resources' => $configuration['resources'] ?? [],
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    public static function applyModifiersToEvents(array $events, array $eventModifiers, array $enabledModifiers): array
    {
        $results = [];

        /** @var CalendarEvent $event */
        foreach ($events as $event) {
            $title = $event->title;

            // Modify title according to event modifiers.
            foreach ($eventModifiers as $modifier) {
                // Make it configurable in the Feed if the modifiers should be enabled.
                if ($modifier['activateInFeed'] && !in_array($modifier['id'], $enabledModifiers)) {
                    continue;
                }

                $pattern = $modifier['pattern'];

                if (self::EXCLUDE_IF_TITLE_NOT_CONTAINS == $modifier['type']) {
                    $match = preg_match($pattern, $title);

                    if (!$match) {
                        continue 2;
                    }

                    if ($modifier['removeTrigger']) {
                        $title = preg_replace($pattern, '', $title);
                    }
                }

                if (self::REPLACE_TITLE_IF_CONTAINS == $modifier['type']) {
                    $match = preg_match($pattern, $title);

                    if ($match) {
                        $title = $modifier['replacement'];
                    }
                }
            }

            $title = trim($title);

            $event->title = $title;

            $results[] = $event;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'resources');

        $result = [
            [
                'key' => 'calendar-api-resource-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $endpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resurser, der skal hentes indgange fra.',
                'formGroupClasses' => 'mb-3',
            ],
        ];

        $enableModifierOptions = [];
        foreach ($this->eventModifiers as $modifier) {
            if (isset($modifier['activateInFeed']) && true === $modifier['activateInFeed']) {
                $enableModifierOptions[] = [
                    'title' => $modifier['title'] ?? $modifier['id'],
                    'description' => $modifier['description'] ?? '',
                    'value' => $modifier['id'],
                ];
            }
        }

        if (count($enableModifierOptions) > 0) {
            $result[] = [
                'key' => 'calendar-api-modifiers',
                'input' => 'checkbox-options',
                'name' => 'enabledModifiers',
                'label' => 'Vælg justeringer af begivenheder',
                'helpText' => 'Her kan du aktivere forskellige justeringer af begivenhederne.',
                'formGroupClasses' => 'mb-3',
                'options' => $enableModifierOptions,
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('resources' === $name) {
                $secrets = $feedSource->getSecrets();
                $locationIds = $secrets['locations'] ?? [];

                $resources = [];

                foreach ($locationIds as $locationId) {
                    $locationResources = $this->getLocationResources($locationId);
                    $resources = array_merge($resources, $locationResources);
                }

                $resourceOptions = array_map(fn (Resource $resource) => [
                    'id' => Ulid::generate(),
                    'title' => $resource->name.' ('.$resource->displayName.')',
                    'value' => $resource->id,
                ], $resources);

                // Sort resource options by title.
                usort($resourceOptions, fn ($a, $b) => strcmp((string) $a['title'], (string) $b['title']));

                return $resourceOptions;
            } elseif ('locations' === $name) {
                $locationOptions = array_map(fn (Location $location) => [
                    'id' => Ulid::generate(),
                    'title' => $location->displayName,
                    'value' => $location->id,
                ], $this->loadLocations());

                usort($locationOptions, fn ($a, $b) => strcmp((string) $a['title'], (string) $b['title']));

                return $locationOptions;
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('CalendarApiFeedType: Failed to get config "{name}" for feed source {feedSourceId} (tenant {tenantKey}): {message}', [
                'name' => $name,
                'feedSourceId' => $feedSource->getId(),
                'tenantKey' => $feedSource->getTenant()->getTenantKey(),
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);
        }

        return null;
    }

    public function getRequiredSecrets(): array
    {
        return [
            'locations' => [
                'type' => 'string_array',
                'options' => $this->getLocationOptions(),
                'exposeValue' => true,
            ],
        ];
    }

    public function getRequiredConfiguration(): array
    {
        return ['resources'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    private function getLocationOptions(): array
    {
        $locations = $this->loadLocations();

        return array_reduce($locations, function (array $carry, Location $location) {
            $carry[] = $location->id;

            return $carry;
        }, []);
    }

    private function getResourceEvents(string $resourceId): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_EVENTS.'-'.$resourceId, function (ItemInterface $cacheItem) use ($resourceId) {
            $cacheItem->expiresAfter($this->cacheExpireSeconds);

            $allEvents = $this->loadEvents();

            return array_filter($allEvents, fn (CalendarEvent $event) => $event->resourceId === $resourceId);
        });
    }

    private function getLocationResources(string $locationId): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_RESOURCES.'-'.$locationId, function (ItemInterface $cacheItem) use ($locationId) {
            $cacheItem->expiresAfter($this->cacheExpireSeconds);

            $allResources = $this->loadResources();

            return array_filter($allResources, fn (Resource $resource) => $resource->locationId === $locationId);
        });
    }

    private function loadLocations(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_LOCATIONS, function (ItemInterface $cacheItem) {
            $cacheItem->expiresAfter($this->cacheExpireSeconds);

            $response = $this->client->request('GET', $this->locationEndpoint);
            $locationEntries = $response->toArray();

            return array_map(fn (array $entry) => new Location(
                $entry[$this->getMapping('locationId')],
                $entry[$this->getMapping('locationDisplayName')],
            ), $locationEntries);
        });
    }

    private function loadResources(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_RESOURCES, function (ItemInterface $cacheItem) {
            $cacheItem->expiresAfter($this->cacheExpireSeconds);

            $response = $this->client->request('GET', $this->resourceEndpoint);
            $resourceEntries = $response->toArray();

            $resources = [];

            foreach ($resourceEntries as $resourceEntry) {
                // Only include resources that are marked as included in events. Defaults to true, if the resourceEntry
                // does not have the property defined by the mapping resourceIncludedInEvents.
                $resourceIncludedInEvents = $resourceEntry[$this->getMapping('resourceIncludedInEvents')] ?? true;
                $includeValue = $this->parseBool($resourceIncludedInEvents);

                // Only include resources that are included in events endpoint.
                if ($includeValue) {
                    $id = $resourceEntry[$this->getMapping('resourceId')];

                    $resource = new Resource(
                        $id,
                        $resourceEntry[$this->getMapping('resourceName')],
                        $resourceEntry[$this->getMapping('resourceLocationId')],
                        $resourceEntry[$this->getMapping('resourceDisplayName')],
                    );

                    $resources[$id] = $resource;
                }
            }

            return $resources;
        });
    }

    private function loadEvents(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_EVENTS, function (ItemInterface $cacheItem) {
            $cacheItem->expiresAfter($this->cacheExpireSeconds);

            $response = $this->client->request('GET', $this->eventEndpoint);
            $eventEntries = $response->toArray();

            return array_reduce($eventEntries, function (array $carry, array $entry) {
                $newEntry = new CalendarEvent(
                    Ulid::generate(),
                    $entry[$this->getMapping('eventTitle')],
                    $this->stringToUnixTimestamp($entry[$this->getMapping('eventStartTime')]),
                    $this->stringToUnixTimestamp($entry[$this->getMapping('eventEndTime')]),
                    $entry[$this->getMapping('eventResourceId')],
                    $entry[$this->getMapping('eventResourceDisplayName')],
                );

                // Filter out entries if they do not supply required data.
                if (
                    !empty($newEntry->startTimeTimestamp)
                    && !empty($newEntry->endTimeTimestamp)
                    && !empty($newEntry->resourceId)
                    && !empty($newEntry->resourceDisplayName)
                ) {
                    $carry[] = $newEntry;
                }

                return $carry;
            }, []);
        });
    }

    private function stringToUnixTimestamp(string $dateTimeString): int
    {
        // Default dateformat is: 'Y-m-d\TH:i:sP'. Example: 2004-02-15T15:19:21+00:00
        // See: https://www.php.net/manual/en/datetime.format.php for available formats.
        $dateFormat = '' !== $this->dateFormat ? $this->dateFormat : \DateTimeInterface::ATOM;
        // Default is no timezone since the difference from UTC is in the dateformat (+00:00).
        // For timezone options see: https://www.php.net/manual/en/timezones.php
        $timezone = !empty($this->timezone) ? new \DateTimeZone($this->timezone) : null;

        $datetime = \DateTime::createFromFormat($dateFormat, $dateTimeString, $timezone);

        if (false === $datetime) {
            $this->logger->warning('Date {date} could not be parsed by format {format}', [
                'date' => $dateTimeString,
                'format' => $dateFormat,
            ]);

            return 0;
        }

        return $datetime->getTimestamp();
    }

    private function parseBool(string|bool $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return 'true' == strtolower($value);
    }

    private function getMapping(string $key): string
    {
        return $this->mappings[$key];
    }

    private function createMappings(array $customMappings): array
    {
        return [
            'locationId' => $customMappings['LOCATION_ID'] ?? 'id',
            'locationDisplayName' => $customMappings['LOCATION_DISPLAY_NAME'] ?? 'displayName',
            'resourceId' => $customMappings['RESOURCE_ID'] ?? 'id',
            'resourceLocationId' => $customMappings['RESOURCE_LOCATION_ID'] ?? 'locationId',
            'resourceName' => $customMappings['RESOURCE_NAME'] ?? 'name',
            'resourceDisplayName' => $customMappings['RESOURCE_DISPLAY_NAME'] ?? 'displayName',
            'resourceIncludedInEvents' => $customMappings['RESOURCE_INCLUDED_IN_EVENTS'] ?? 'includedInEvents',
            'eventTitle' => $customMappings['EVENT_TITLE'] ?? 'title',
            'eventStartTime' => $customMappings['EVENT_START_TIME'] ?? 'startTime',
            'eventEndTime' => $customMappings['EVENT_END_TIME'] ?? 'endTime',
            'eventResourceId' => $customMappings['EVENT_RESOURCE_ID'] ?? 'resourceId',
            'eventResourceDisplayName' => $customMappings['EVENT_RESOURCE_DISPLAY_NAME'] ?? 'displayName',
        ];
    }

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'locations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'required' => ['locations'],
        ];
    }
}
