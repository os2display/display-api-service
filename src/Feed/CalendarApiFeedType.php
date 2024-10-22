<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Model\CalendarEvent;
use App\Model\CalendarResource;
use App\Service\FeedService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendarApiFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = 'calendar';

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $calendarApiCache,
        private readonly array $calendarApiEndpoints,
        private readonly array $calendarApiMappings,
    )
    {
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

            $filterList = $configuration['filterList'] ?? false;
            $rewriteBookedTitles = $configuration['rewriteBookedTitles'] ?? false;

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

                    // TODO: Make filters configurable.
                    // TODO: Make rewrites configurable.

                    // Apply list filter. If enabled it removes all events that do not have (liste) in title.
                    if ($filterList) {
                        if (!str_contains($title, '(liste)')) {
                            continue;
                        } else {
                            $title = str_replace('(liste)', '', $title);
                        }
                    }

                    // Apply booked title override. If enabled it changes the title to Optaget if it contains (optaget).
                    if ($rewriteBookedTitles) {
                        if (str_contains($title, '(optaget)')) {
                            $title = 'Optaget';
                        }
                    }

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
            usort($results, fn($a, $b) => strcmp((string)$a['startTime'], (string)$b['startTime']));

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

        // @TODO: Translation.
        return [
            [
                'key' => 'calendar-api-resource-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $endpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resurser, der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
            [
                'key' => 'calendar-api-resource-rewrite-booked',
                'input' => 'checkbox',
                'name' => 'rewriteBookedTitles',
                'label' => 'Omskriv titler med (optaget)',
                'helpText' => 'Denne mulighed gør, at titler som indeholder (optaget) bliver omskrevet til "Optaget".',
                'formGroupClasses' => 'col mb-3',
            ],
            [
                'key' => 'calendar-api-resource-filter-not-list',
                'input' => 'checkbox',
                'name' => 'filterList',
                'label' => 'Vis kun begivenheder med (liste) i titlen',
                'helpText' => 'Denne mulighed fjerner begivenheder der IKKE har (liste) i titlen. Den fjerner også (liste) fra titlen.',
                'formGroupClasses' => 'col mb-3',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('resources' === $name) {
                $secrets = $feedSource->getSecrets();
                $locationIds = $secrets->locationIds ?? [];

                $resources = [];

                foreach ($locationIds as $locationId) {
                    $resources = array_unique(array_merge($resources, $this->getLocationResources($locationId)));
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
        return ['locations'];
    }

    public function getRequiredConfiguration(): array
    {
        return ['resources'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getResourceEvents(string $resourceId): array
    {
        return $this->calendarApiCache->get('events-'.$resourceId, function (ItemInterface $item) use ($resourceId): array {
            // TODO: Make this value configurable.
            $item->expiresAfter(60 * 5);
            $allEvents = $this->loadEvents();

            return array_filter($allEvents, fn(CalendarEvent $item) => $item->resourceId === $resourceId);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getLocationResources(string $locationId): array
    {
        return $this->calendarApiCache->get('resources-'.$locationId, function (ItemInterface $item) use ($locationId): array {
            // TODO: Make this value configurable.
            $item->expiresAfter(60 * 5);
            $allResources = $this->loadResources();

            return array_filter($allResources, fn(CalendarResource $item) => $item->locationId === $locationId);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    private function loadResources(): array
    {
        return $this->calendarApiCache->get('resources', function (ItemInterface $item): array  {
            // TODO: Make this value configurable.
            $item->expiresAfter(60 * 5);

            $response = $this->client->request('GET', $this->getEndpoint('resource'));

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
        return $this->calendarApiCache->get('events', function (ItemInterface $item): array {
            // TODO: Make configurable.
            $item->expiresAfter(60 * 5);
            $response = $this->client->request('GET', $this->getEndpoint('event'));

            $eventEntries = $response->toArray();

            return array_map(function (array $entry) {
                return new CalendarEvent(
                    $entry[$this->getMapping('eventId')],
                    $entry[$this->getMapping('eventTitle')],
                    $this->stringToUnixTimestamp($entry[$this->getMapping('eventStartTime')]),
                    $this->stringToUnixTimestamp($entry[$this->getMapping('eventEndTime')]),
                    $entry[$this->getMapping('eventResourceId')],
                    $entry[$this->getMapping('eventResourceDisplayName')],
                );
            }, $eventEntries);
        });
    }

    private function stringToUnixTimestamp(string $dateTimeString): int
    {
        // TODO: Handle date format. Make configurable.
        // return (\DateTime::createFromFormat('c', $dateTimeString))->getTimestamp();
        return (new \DateTimeImmutable($dateTimeString))->getTimestamp();
    }

    private function parseBool(string|bool $value): bool
    {
        if (is_bool($value)) {
            return $value;
        } else {
            return strtolower($value) == 'true';
        }
    }

    private function getEndpoint(string $key): string
    {
        return $this->calendarApiEndpoints[$key];
    }

    private function getMapping(string $key): string
    {
        return $this->calendarApiMappings[$key];
    }
}
