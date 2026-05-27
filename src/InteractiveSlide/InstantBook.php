<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

use App\Entity\Tenant;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\InteractiveSlideConfig;
use App\Entity\Tenant\Slide;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\TooManyRequestsException;
use App\Feed\FeedOutputModels;
use App\Service\FeedService;
use App\Service\InteractiveSlideService;
use App\Service\KeyVaultService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interactive slide that allows for performing quick bookings of resources.
 *
 * Only resources attached to the slide through slide.feed.configuration.resources can be booked from the slide.
 */
class InstantBook implements InteractiveSlideInterface
{
    private const string ACTION_GET_QUICK_BOOK_OPTIONS = 'ACTION_GET_QUICK_BOOK_OPTIONS';
    private const string ACTION_QUICK_BOOK = 'ACTION_QUICK_BOOK';
    private const string ENDPOINT = 'https://graph.microsoft.com/v1.0';
    private const string LOGIN_ENDPOINT = 'https://login.microsoftonline.com/';
    private const string OAUTH_PATH = '/oauth2/v2.0/token';
    private const string SCOPE = 'https://graph.microsoft.com/.default';
    private const string GRANT_TYPE = 'password';
    private const string CACHE_PREFIX = 'MS-INSTANT-BOOK';
    private const string CACHE_ALLOWED_RESOURCES_PREFIX = 'INSTANT-BOOK-ALLOWED-RESOURCES-';
    private const string CACHE_KEY_TOKEN_PREFIX = self::CACHE_PREFIX.'-TOKEN-';
    private const string CACHE_KEY_OPTIONS_PREFIX = self::CACHE_PREFIX.'-OPTIONS-';
    private const string CACHE_PREFIX_SPAM_PROTECT_PREFIX = self::CACHE_PREFIX.'-SPAM-PROTECT-';
    private const string CACHE_KEY_RESOURCES = self::CACHE_PREFIX.'-RESOURCES';
    private const string BOOKING_TITLE = 'Straksbooking';
    private const array DURATIONS = [15, 30, 60];
    private const string CACHE_KEY_BUSY_INTERVALS_PREFIX = self::CACHE_PREFIX.'-BUSY-INTERVALS';
    private const string CACHE_LIFETIME_QUICK_BOOK_OPTIONS = 'PT5M';
    private const string CACHE_LIFETIME_BUSY_INTERVALS = 'PT15M';
    private const string CACHE_LIFETIME_QUICK_BOOK_SPAM_PROTECT = 'PT1M';
    public const string SOURCE_GRAPH = 'graph';
    public const string SOURCE_FEED = 'feed';
    // see https://docs.microsoft.com/en-us/graph/api/resources/datetimetimezone?view=graph-rest-1.0
    // example 2019-03-15T09:00:00
    public const string GRAPH_DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly InteractiveSlideService $interactiveService,
        private readonly HttpClientInterface $client,
        private readonly KeyVaultService $keyVaultService,
        private readonly CacheInterface $interactiveSlideCache,
        private readonly FeedService $feedService,
        private readonly string $busyIntervalsSource,
    ) {
        if (!in_array($busyIntervalsSource, [self::SOURCE_GRAPH, self::SOURCE_FEED], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid INSTANT_BOOK_BUSY_INTERVALS_SOURCE "%s"; expected "%s" or "%s".', $busyIntervalsSource, self::SOURCE_GRAPH, self::SOURCE_FEED));
        }
    }

    public function getConfigOptions(): array
    {
        // All secrets are retrieved from the KeyVault. Therefore, the input for the different configurations are the
        // keys into the KeyVault where the values can be retrieved.
        return [
            'tenantId' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the tenant id of the Microsoft Graph App',
            ],
            'clientId' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the client id of the Microsoft Graph App',
            ],
            'username' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the username that should perform the action.',
            ],
            'password' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the password of the user.',
            ],
            'resourceEndpoint' => [
                'required' => false,
                'description' => 'The key in the KeyVault for the resources endpoint. This should supply a json list of resources that can be booked. The resources should have ResourceMail and allowInstantBooking ("True"/"False") properties set.',
            ],
        ];
    }

    /**
     * @throws ConflictException
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws NotAcceptableException
     * @throws TooManyRequestsException
     * @throws \Throwable
     */
    public function performAction(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        return match ($interactionRequest->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($tenant, $slide, $interactionRequest),
            self::ACTION_QUICK_BOOK => $this->quickBook($tenant, $slide, $interactionRequest),
            default => throw new NotAcceptableException('Action not supported'),
        };
    }

    /**
     * @throws \Throwable
     * @throws NotAcceptableException
     */
    private function authenticate(array $configuration): array
    {
        $tenantId = $this->keyVaultService->getValue($configuration['tenantId']);
        $clientId = $this->keyVaultService->getValue($configuration['clientId']);
        $username = $this->keyVaultService->getValue($configuration['username']);
        $password = $this->keyVaultService->getValue($configuration['password']);

        if (4 !== count(array_filter([$tenantId, $clientId, $username, $password]))) {
            throw new NotAcceptableException('tenantId, clientId, username, password must all be set.');
        }

        $url = self::LOGIN_ENDPOINT.$tenantId.self::OAUTH_PATH;

        $response = $this->client->request('POST', $url, [
            'body' => [
                'client_id' => $clientId,
                'scope' => self::SCOPE,
                'username' => $username,
                'password' => $password,
                'grant_type' => self::GRANT_TYPE,
            ],
        ]);

        return $response->toArray();
    }

    /**
     * @throws NotAcceptableException
     * @throws InvalidArgumentException
     */
    private function getToken(Tenant $tenant, InteractiveSlideConfig $interactive): string
    {
        $configuration = $interactive->getConfiguration();

        if (null === $configuration) {
            throw new NotAcceptableException('InteractiveSlide has no configuration');
        }

        return $this->interactiveSlideCache->get(
            self::CACHE_KEY_TOKEN_PREFIX.$tenant->getTenantKey(),
            function (CacheItemInterface $item) use ($configuration): mixed {
                $arr = $this->authenticate($configuration);

                $item->expiresAfter($arr['expires_in']);

                return $arr['access_token'];
            },
        );
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    private function getQuickBookOptions(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $resource = $interactionRequest->data['resource'] ?? null;

        if (null === $resource) {
            throw new BadRequestException('Resource not set.');
        }

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));

        $siblingEntries = [];
        $updatedWatchedResources = [];

        $result = $this->interactiveSlideCache->get(self::CACHE_KEY_OPTIONS_PREFIX.$resource,
            function (CacheItemInterface $item) use ($slide, $resource, $interactionRequest, $start, $tenant, &$siblingEntries, &$updatedWatchedResources) {
                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS));

                // If any exceptions are thrown we return an empty options entry.
                try {
                    $interactiveSlideConfig = $this->validateResourceAccess($tenant, $slide, $interactionRequest->implementationClass, $resource);
                    $token = $this->getToken($tenant, $interactiveSlideConfig);

                    $startPlus1Hour = (clone $start)->add(new \DateInterval('PT1H'))->setTimezone(new \DateTimeZone('UTC'));

                    // Get resources that are watched for availability.
                    $watchedResources = $this->interactiveSlideCache->get(self::CACHE_KEY_RESOURCES, function (CacheItemInterface $item) {
                        $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS));

                        return [];
                    });

                    // Add resource to watchedResources, if not in list.
                    if (!in_array($resource, $watchedResources)) {
                        $watchedResources[] = $resource;
                    }

                    $schedules = $this->fetchBusyIntervals($token, $watchedResources, $start, $startPlus1Hour, $slide);

                    $result = [];

                    // Compute entries for all watched resources.
                    foreach ($watchedResources as $key => $watchResource) {
                        $schedule = $schedules[$watchResource] ?? null;

                        if (!isset($schedules[$watchResource])) {
                            unset($watchedResources[$key]);
                        }

                        $entry = $this->createEntry($watchResource, $start, $schedule);

                        if ($watchResource == $resource) {
                            $result = $entry;
                        } else {
                            $siblingEntries[$watchResource] = $entry;
                        }
                    }

                    $updatedWatchedResources = $watchedResources;

                    return $result;
                } catch (\Throwable) {
                    // All errors should result in empty options.
                    return $this->createEntry($resource, $start);
                }
            }
        );

        // Refresh sibling cache entries and watched resources list outside the callback.
        // $siblingEntries is only populated on cache miss (when the callback ran).
        foreach ($siblingEntries as $watchResource => $entry) {
            $this->setCacheValue(self::CACHE_KEY_OPTIONS_PREFIX.$watchResource, $entry, self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS);
        }

        if (!empty($updatedWatchedResources)) {
            $this->setCacheValue(self::CACHE_KEY_RESOURCES, $updatedWatchedResources, self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS);
        }

        return $result;
    }

    private function createEntry(string $resource, \DateTime $start, ?array $schedules = null): array
    {
        $startFormatted = $start->format('c');

        $entry = [
            'resource' => $resource,
            'from' => $startFormatted,
            'options' => [],
        ];

        if (null === $schedules) {
            return $entry;
        }

        foreach (self::DURATIONS as $durationMinutes) {
            try {
                $startPlus = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));
            } catch (\Exception) {
                continue;
            }

            if ($this->intervalFree($schedules, $start, $startPlus)) {
                $entry['options'][] = [
                    'durationMinutes' => $durationMinutes,
                    'to' => $startPlus->format('c'),
                ];
            }
        }

        return $entry;
    }

    /**
     * @throws TooManyRequestsException
     * @throws ConflictException
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws NotAcceptableException
     * @throws ForbiddenException
     * @throws \Throwable
     */
    private function quickBook(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $resource = (string) $this->getValueFromInterval('resource', $interactionRequest);
        $durationMinutes = (int) $this->getValueFromInterval('durationMinutes', $interactionRequest);

        if (!in_array($durationMinutes, self::DURATIONS, true)) {
            throw new BadRequestException('Invalid duration. Allowed values: '.implode(', ', self::DURATIONS));
        }

        // Make sure that booking requests are not spammed.
        // The callback only runs on cache miss (no recent booking exists).
        // On cache hit, $isFreshRequest stays false and we rate-limit.
        $isFreshRequest = false;

        $this->interactiveSlideCache->get(
            self::CACHE_PREFIX_SPAM_PROTECT_PREFIX.$resource,
            function (CacheItemInterface $item) use (&$isFreshRequest): bool {
                $isFreshRequest = true;
                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_SPAM_PROTECT));

                return true;
            }
        );

        if (!$isFreshRequest) {
            throw new TooManyRequestsException('Service unavailable');
        }

        $interactiveSlideConfig = $this->validateResourceAccess($tenant, $slide, $interactionRequest->implementationClass, $resource);
        $token = $this->getToken($tenant, $interactiveSlideConfig);

        $configuration = $interactiveSlideConfig->getConfiguration();

        if (null === $configuration) {
            throw new NotAcceptableException('InteractiveSlideConfig has no configuration');
        }

        $username = $this->keyVaultService->getValue($configuration['username']);

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
        $startPlusDuration = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));

        // Pre-check the slot against live Graph state. This is required, not just an
        // optimization: the resources used here are not configured to block conflicting
        // bookings at the Graph API level — POSTing a conflicting booking will simply
        // succeed rather than return 409. The 409 catch below is only a backstop for
        // resources whose AutomateProcessing setting does reject conflicts.
        $schedules = $this->getBusyIntervals($token, [$resource], $start, $startPlusDuration);

        if (!$this->intervalFree($schedules[$resource] ?? [], $start, $startPlusDuration)) {
            throw new ConflictException('Interval booked already');
        }

        $requestBody = [
            'subject' => self::BOOKING_TITLE,
            'start' => [
                'dateTime' => $start->format(self::GRAPH_DATE_FORMAT),
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => $startPlusDuration->format(self::GRAPH_DATE_FORMAT),
                'timeZone' => 'UTC',
            ],
            'allowNewTimeProposals' => false,
            'showAs' => 'busy',
            'isOrganizer' => false,
            'location' => [
                'locationEmailAddress' => $resource,
            ],
            'attendees' => [
                [
                    'emailAddress' => [
                        'address' => $username,
                    ],
                    'type' => 'optional',
                ],
            ],
        ];

        $response = $this->client->request('POST', self::ENDPOINT.'/users/'.$resource.'/events', [
            'headers' => $this->getHeaders($token),
            'body' => json_encode($requestBody),
        ]);

        $status = $response->getStatusCode();

        if (409 === $status) {
            throw new ConflictException('Interval booked already');
        }

        if ($status >= 400) {
            throw new NotAcceptableException('Booking failed with status '.$status);
        }

        return [
            'status' => $status,
            'interval' => [
                'from' => $start->format('c'),
                'to' => $startPlusDuration->format('c'),
            ],
        ];
    }

    /**
     * @see https://docs.microsoft.com/en-us/graph/api/calendar-getschedule?view=graph-rest-1.0&tabs=http
     *
     * @throws \Throwable
     */
    private function getBusyIntervals(string $token, array $resources, \DateTime $startTime, \DateTime $endTime): array
    {
        $body = [
            'schedules' => $resources,
            'availabilityViewInterval' => '15',
            'startTime' => [
                'dateTime' => $startTime->setTimezone(new \DateTimeZone('UTC'))->format(self::GRAPH_DATE_FORMAT),
                'timeZone' => 'UTC',
            ],
            'endTime' => [
                'dateTime' => $endTime->setTimezone(new \DateTimeZone('UTC'))->format(self::GRAPH_DATE_FORMAT),
                'timeZone' => 'UTC',
            ],
        ];

        $result = [];
        $url = self::ENDPOINT.'/me/calendar/getSchedule';

        do {
            $response = $this->client->request('POST', $url, [
                'headers' => $this->getHeaders($token),
                'body' => json_encode($body),
            ]);

            $data = $response->toArray();

            foreach ($data['value'] as $schedule) {
                $scheduleId = $schedule['scheduleId'] ?? null;
                $scheduleItems = $schedule['scheduleItems'] ?? null;

                if (null === $scheduleId || null === $scheduleItems) {
                    continue;
                }

                if (!isset($result[$scheduleId])) {
                    $result[$scheduleId] = [];
                }

                foreach ($scheduleItems as $scheduleItem) {
                    $eventStartArray = $scheduleItem['start'];
                    $eventEndArray = $scheduleItem['end'];

                    $start = new \DateTime($eventStartArray['dateTime'], new \DateTimeZone($eventStartArray['timeZone']));
                    $end = new \DateTime($eventEndArray['dateTime'], new \DateTimeZone($eventEndArray['timeZone']));

                    $result[$scheduleId][] = [
                        'startTime' => $start,
                        'endTime' => $end,
                    ];
                }
            }

            $url = $data['@odata.nextLink'] ?? null;
        } while (null !== $url);

        return $result;
    }

    /**
     * Dispatch busy-intervals lookup according to INSTANT_BOOK_BUSY_INTERVALS_SOURCE.
     *
     * Both branches return the same shape:
     * [resourceId => [['startTime' => DateTime, 'endTime' => DateTime], ...]].
     *
     * @param string[] $resources
     *
     * @return array<string, array<int, array{startTime: \DateTime, endTime: \DateTime}>>
     *
     * @throws \Throwable
     */
    private function fetchBusyIntervals(string $token, array $resources, \DateTime $from, \DateTime $to, Slide $slide): array
    {
        return match ($this->busyIntervalsSource) {
            self::SOURCE_FEED => $this->getBusyIntervalsFromFeed($slide->getFeed(), $resources, $from, $to),
            self::SOURCE_GRAPH => $this->getBusyIntervalsCached($token, $resources, $from, $to),
        };
    }

    /**
     * Cached wrapper around getBusyIntervals().
     *
     * The cache key includes the resource set and the from/to window (minute precision) so that
     * concurrent requests with different watched resources or windows do not share an entry; the
     * previous fixed-key behaviour caused different slides to read each other's data.
     *
     * @param string[] $resources
     *
     * @return array<string, array<int, array{startTime: \DateTime, endTime: \DateTime}>>
     *
     * @throws InvalidArgumentException
     */
    private function getBusyIntervalsCached(string $token, array $resources, \DateTime $from, \DateTime $to): array
    {
        $sortedResources = $resources;
        sort($sortedResources);

        $cacheKey = sprintf(
            '%s-%s-%s-%s',
            self::CACHE_KEY_BUSY_INTERVALS_PREFIX,
            hash('xxh128', implode('|', $sortedResources)),
            $from->format('YmdHi'),
            $to->format('YmdHi'),
        );

        return $this->interactiveSlideCache->get(
            $cacheKey,
            function (CacheItemInterface $item) use ($token, $resources, $from, $to) {
                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_BUSY_INTERVALS));

                return $this->getBusyIntervals($token, $resources, $from, $to);
            },
        );
    }

    /**
     * Derive busy intervals from the slide's calendar-output-model feed.
     *
     * @param string[] $resources resource ids to include in the result
     *
     * @return array<string, array<int, array{startTime: \DateTime, endTime: \DateTime}>>
     *
     * @throws UnprocessableEntityHttpException when the slide is not configured for feed-sourced busy intervals
     */
    private function getBusyIntervalsFromFeed(?Feed $feed, array $resources, \DateTime $from, \DateTime $to): array
    {
        if (null === $feed) {
            throw new UnprocessableEntityHttpException('InstantBook (feed source): slide feed not set.');
        }

        $feedSource = $feed->getFeedSource();

        if (null === $feedSource) {
            throw new UnprocessableEntityHttpException('InstantBook (feed source): feed source not set on slide feed.');
        }

        $feedType = $this->feedService->getFeedType($feedSource->getFeedType());

        if (FeedOutputModels::CALENDAR_OUTPUT !== $feedType->getSupportedFeedOutputType()) {
            throw new UnprocessableEntityHttpException('InstantBook (feed source) requires a calendar-output feed.');
        }

        $events = $this->feedService->getData($feed) ?? [];

        $result = array_fill_keys($resources, []);
        $fromTs = $from->getTimestamp();
        $toTs = $to->getTimestamp();

        foreach ($events as $event) {
            $resourceId = $event['resourceId'] ?? null;

            if (!is_string($resourceId) || !array_key_exists($resourceId, $result)) {
                continue;
            }

            $startTs = (int) ($event['startTime'] ?? 0);
            $endTs = (int) ($event['endTime'] ?? 0);

            if ($endTs <= $fromTs || $startTs >= $toTs) {
                continue;
            }

            $result[$resourceId][] = [
                'startTime' => new \DateTime('@'.$startTs),
                'endTime' => new \DateTime('@'.$endTs),
            ];
        }

        return $result;
    }

    public function intervalFree(array $schedule, \DateTime $from, \DateTime $to): bool
    {
        foreach ($schedule as $scheduleEntry) {
            if (!($scheduleEntry['startTime'] >= $to || $scheduleEntry['endTime'] <= $from)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws BadRequestException
     */
    private function getValueFromInterval(string $key, InteractionSlideRequest $interactionRequest): string|int
    {
        $interval = $interactionRequest->data['interval'] ?? null;

        if (null === $interval) {
            throw new BadRequestException('interval not set.');
        }

        $value = $interval[$key] ?? null;

        if (null === $value) {
            throw new BadRequestException("interval.{$key} not set.");
        }

        return $value;
    }

    private function getHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @throws NotAcceptableException
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     */
    private function validateResourceAccess(Tenant $tenant, Slide $slide, string $implementationClass, string $resource): InteractiveSlideConfig
    {
        $interactiveSlideConfig = $this->interactiveService->getInteractiveSlideConfig($tenant, $implementationClass);

        if (null === $interactiveSlideConfig) {
            throw new NotAcceptableException('InteractiveSlideConfig not found');
        }

        $this->checkPermission($interactiveSlideConfig, $resource);

        $feed = $slide->getFeed();

        if (null === $feed) {
            throw new NotAcceptableException('Slide feed not set.');
        }

        if (!in_array($resource, $feed->getConfiguration()['resources'] ?? [])) {
            throw new NotAcceptableException('Resource not in feed resources');
        }

        return $interactiveSlideConfig;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function setCacheValue(string $key, mixed $value, string $lifetime): void
    {
        $this->interactiveSlideCache->delete($key);
        $this->interactiveSlideCache->get($key, function (CacheItemInterface $item) use ($value, $lifetime): mixed {
            $item->expiresAfter(new \DateInterval($lifetime));

            return $value;
        });
    }

    /**
     * @throws NotAcceptableException
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     */
    private function checkPermission(InteractiveSlideConfig $interactive, string $resource): void
    {
        $configuration = $interactive->getConfiguration();

        // Will only limit access to resources if list is set up.
        if (null !== $configuration && !empty($configuration['resourceEndpoint'])) {
            $allowedResources = $this->getAllowedResources($interactive);

            if (!in_array($resource, $allowedResources)) {
                throw new ForbiddenException('Not allowed');
            }
        }
    }

    /**
     * @throws NotAcceptableException
     * @throws InvalidArgumentException
     */
    private function getAllowedResources(InteractiveSlideConfig $interactive): array
    {
        $configuration = $interactive->getConfiguration();

        $key = $configuration['resourceEndpoint'] ?? null;

        if (null === $key) {
            throw new NotAcceptableException('resourceEndpoint not set');
        }

        $resourceEndpoint = $this->keyVaultService->getValue($key);

        if (null === $resourceEndpoint) {
            throw new NotAcceptableException('resourceEndpoint value not set');
        }

        return $this->interactiveSlideCache->get(self::CACHE_ALLOWED_RESOURCES_PREFIX.$interactive->getId(), function (CacheItemInterface $item) use ($resourceEndpoint) {
            $item->expiresAfter(60 * 60);

            $response = $this->client->request('GET', $resourceEndpoint);
            $content = $response->toArray();

            $allowedResources = [];

            foreach ($content as $resource) {
                if ('True' === $resource['allowInstantBooking']) {
                    $allowedResources[] = $resource['ResourceMail'];
                }
            }

            return $allowedResources;
        });
    }
}
