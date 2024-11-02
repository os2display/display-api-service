<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Model\CalendarEvent;
use App\Model\CalendarLocation;
use App\Model\CalendarResource;
use App\Service\FeedService;
use Psr\Cache\InvalidArgumentException;
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
    final public const string SUPPORTED_FEED_TYPE = SupportedFeedOutputs::CALENDAR_OUTPUT;
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
        private readonly int $cacheExpireResourcesSeconds,
        private readonly int $cacheExpireEventsSeconds
    )
    {
        $this->mappings = $this->createMappings($this->customMappings);
    }

    /**
     * @param Feed $feed
     *
     * @return array
     */
    public function getData(Feed $feed): array
    {
        try {
            $results = [];

            $configuration = $feed->getConfiguration();

            $enabledModifiers = $configuration['enabledModifiers'] ?? [];

            if (!isset($configuration['resources'])) {
                $this->logger->error('CalendarApiFeedType: Resources not set.');
                return [];
            }

            $resources = $configuration['resources'];
            foreach ($resources as $resource) {
                $events = $this->getResourceEvents($resource);

                /** @var CalendarEvent $event */
                foreach ($events as $event) {
                    $title = $event->title;

                    // Modify title according to event modifiers.
                    foreach ($this->eventModifiers as $modifier) {
                        // Make it configurable in the Feed if the modifiers should be enabled.
                        if ($modifier['activateInFeed'] && !in_array($modifier['id'], $enabledModifiers)) {
                            continue;
                        }

                        if ($modifier['type'] == self::EXCLUDE_IF_TITLE_NOT_CONTAINS) {
                            $match = preg_match("/".$modifier['trigger']."/".(!$modifier['caseSensitive'] ? 'i' : ''), $title);

                            if ($modifier['removeTrigger']) {
                                $title = str_replace($modifier['trigger'], "", $title);
                            }

                            if (!$match) {
                                continue;
                            }
                        }

                        if ($modifier['type'] == self::REPLACE_TITLE_IF_CONTAINS) {
                            $match = preg_match("/".$modifier['trigger']."/".(!$modifier['caseSensitive'] ? 'i' : ''), $title);

                            if ($modifier['removeTrigger']) {
                                $title = str_replace($modifier['trigger'], "", $title);
                            }

                            if ($match) {
                                $title = $modifier['replacement'];
                            }
                        }
                    }

                    $title = trim($title);

                    $results[] = [
                        'id' => Ulid::generate(),
                        'title' => $title,
                        'startTime' => $event->startTimeTimestamp,
                        'endTime' => $event->endTimeTimestamp,
                        'resourceTitle' => $event->resourceDisplayName,
                        'resourceId' => $event->resourceId
                    ];
                }
            }

            // Sort bookings by start time.
            usort($results, fn($a, $b) => $a['startTime'] > $b['startTime']);

            return $results;
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);
        }

        return [];
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
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];

        $enableModifierOptions = [];
        foreach ($this->eventModifiers as $modifier) {
            if ($modifier['activateInFeed'] ?? false) {
                $enableModifierOptions[] = [
                    "title" => $modifier['title'] ?? $modifier['id'],
                    "description" => $modifier['description'] ?? '',
                    "value" => $modifier['id'],
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
                'formGroupClasses' => 'col-md-6 mb-3',
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

                $resourceOptions = array_map(function (CalendarResource $resource) {
                    return [
                        'id' => Ulid::generate(),
                        'title' => $resource->displayName,
                        'value' => $resource->id,
                    ];
                }, $resources);

                // Sort resource options by title.
                usort($resourceOptions, fn($a, $b) => strcmp((string)$a['title'], (string)$b['title']));

                return $resourceOptions;
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
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

        return array_reduce($locations, function (array $carry, CalendarLocation $location) {
            $carry[] = $location->id;

            return $carry;
        }, []);
    }

    private function getResourceEvents(string $resourceId): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_EVENTS.'-'.$resourceId, function (ItemInterface $item) use ($resourceId): array {
            $item->expiresAfter($this->cacheExpireEventsSeconds);
            $allEvents = $this->loadEvents();

            return array_filter($allEvents, fn(CalendarEvent $item) => $item->resourceId === $resourceId);
        });
    }

    private function getLocationResources(string $locationId): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_RESOURCES.'-'.$locationId, function (ItemInterface $item) use ($locationId): array {
            $item->expiresAfter($this->cacheExpireResourcesSeconds);
            $allResources = $this->loadResources();

            return array_filter($allResources, fn(CalendarResource $item) => $item->locationId === $locationId);
        });
    }

    private function loadLocations(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_LOCATIONS, function (ItemInterface $item): array {
            $item->expiresAfter($this->cacheExpireResourcesSeconds);

            $response = $this->client->request('GET', $this->locationEndpoint);

            $LocationEntries = $response->toArray();

            return array_map(function (array $entry) {
                return new CalendarLocation(
                    $entry[$this->getMapping('locationId')],
                    $entry[$this->getMapping('locationDisplayName')],
                );
            }, $LocationEntries);
        });
    }

    private function loadResources(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_RESOURCES, function (ItemInterface $item): array  {
            $item->expiresAfter($this->cacheExpireResourcesSeconds);

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
                    $resource = new CalendarResource(
                        $resourceEntry[$this->getMapping('resourceId')],
                        $resourceEntry[$this->getMapping('resourceLocationId')],
                        $resourceEntry[$this->getMapping('resourceDisplayName')],
                    );

                    $resources[] = $resource;
                }
            }

            return $resources;
        });
    }

    private function loadEvents(): array
    {
        return $this->calendarApiCache->get(self::CACHE_KEY_EVENTS, function (ItemInterface $item): array {
            $item->expiresAfter($this->cacheExpireEventsSeconds);
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
                    !empty($newEntry->startTimeTimestamp) &&
                    !empty($newEntry->endTimeTimestamp) &&
                    !empty($newEntry->resourceId) &&
                    !empty($newEntry->resourceDisplayName)
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
        $dateFormat = $this->dateFormat !== '' ? $this->dateFormat : \DateTimeInterface::ATOM;
        // Default is no timezone since the difference from UTC is in the dateformat (+00:00).
        // For timezone options see: https://www.php.net/manual/en/timezones.php
        $timezone = $this->timezone !== '' ? new \DateTimeZone($this->timezone) : null;

        $datetime = \DateTime::createFromFormat($dateFormat, $dateTimeString, $timezone);

        if ($datetime === false) {
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
        } else {
            return strtolower($value) == 'true';
        }
    }

    private function getMapping(string $key): string
    {
        return $this->mappings[$key];
    }

    private function createMappings(array $customMappings): array
    {
        return [
            "locationId" => $customMappings["LOCATION_ID"] ?? "id",
            "locationDisplayName" => $customMappings["LOCATION_DISPLAY_NAME"] ?? "displayName",
            "resourceId" => $customMappings["RESOURCE_ID"] ?? "id",
            "resourceLocationId" => $customMappings["RESOURCE_LOCATION_ID"] ?? "locationId",
            "resourceDisplayName" => $customMappings["RESOURCE_DISPLAY_NAME"] ?? "displayName",
            "resourceIncludedInEvents" => $customMappings["RESOURCE_INCLUDED_IN_EVENTS"] ?? "includedInEvents",
            "eventTitle" => $customMappings["EVENT_TITLE"] ?? "title",
            "eventStartTime" => $customMappings["EVENT_START_TIME"] ?? "startTime",
            "eventEndTime" => $customMappings["EVENT_END_TIME"] ?? "endTime",
            "eventResourceId" => $customMappings["EVENT_RESOURCE_ID"] ?? "resourceId",
            "eventResourceDisplayName" => $customMappings["EVENT_RESOURCE_DISPLAY_NAME"] ?? "displayName"
        ];
    }
}
