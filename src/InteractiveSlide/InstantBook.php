<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

use App\Entity\ScreenUser;
use App\Entity\Tenant;
use App\Entity\Tenant\InteractiveSlide;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Service\InteractiveSlideService;
use App\Service\KeyVaultService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
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
    private const string CACHE_LIFETIME_QUICK_BOOK_OPTIONS = 'PT5M';
    private const string CACHE_LIFETIME_QUICK_BOOK_SPAM_PROTECT = 'PT1M';
    // see https://docs.microsoft.com/en-us/graph/api/resources/datetimetimezone?view=graph-rest-1.0
    // example 2019-03-15T09:00:00
    public const string GRAPH_DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly InteractiveSlideService $interactiveService,
        private readonly Security $security,
        private readonly HttpClientInterface $client,
        private readonly KeyVaultService $keyValueService,
        private readonly CacheInterface $interactiveSlideCache,
    ) {}

    public function getConfigOptions(): array
    {
        return [
            'tenantId' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the tenant id of the App',
            ],
            'clientId' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the client id of the App',
            ],
            'username' => [
                'required' => true,
                'description' => 'The key in the KeyVault for the Microsoft Graph username that should perform the action.',
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

    public function performAction(UserInterface $user, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        return match ($interactionRequest->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($slide, $interactionRequest),
            self::ACTION_QUICK_BOOK => $this->quickBook($slide, $interactionRequest),
            default => throw new BadRequestHttpException('Action not allowed'),
        };
    }

    /**
     * @throws \Throwable
     */
    private function authenticate(array $configuration): array
    {
        $tenantId = $this->keyValueService->getValue($configuration['tenantId']);
        $clientId = $this->keyValueService->getValue($configuration['clientId']);
        $username = $this->keyValueService->getValue($configuration['username']);
        $password = $this->keyValueService->getValue($configuration['password']);

        if (4 !== count(array_filter([$tenantId, $clientId, $username, $password]))) {
            throw new BadRequestHttpException('tenantId, clientId, username, password must all be set.');
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
     * @throws InvalidArgumentException
     */
    private function getToken(Tenant $tenant, InteractiveSlide $interactive): string
    {
        $configuration = $interactive->getConfiguration();

        if (null === $configuration) {
            throw new BadRequestHttpException('Interactive no configuration');
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
     * @throws \Throwable
     */
    private function getQuickBookOptions(Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $resource = $interactionRequest->data['resource'] ?? null;

        if (null === $resource) {
            throw new \Exception('Resource not set.');
        }

        return $this->interactiveSlideCache->get(self::CACHE_KEY_OPTIONS_PREFIX.$resource,
            function (CacheItemInterface $item) use ($slide, $resource, $interactionRequest) {
                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS));

                /** @var User|ScreenUser $activeUser */
                $activeUser = $this->security->getUser();
                $tenant = $activeUser->getActiveTenant();

                $interactive = $this->interactiveService->getInteractiveSlide($tenant, $interactionRequest->implementationClass);

                if (null === $interactive) {
                    throw new \Exception('InteractiveNotFound');
                }

                // Optional limiting of available resources.
                $this->checkPermission($interactive, $resource);

                $feed = $slide->getFeed();

                if (null === $feed) {
                    throw new \Exception('Slide.feed not set.');
                }

                if (!in_array($resource, $feed->getConfiguration()['resources'] ?? [])) {
                    throw new \Exception('Resource not in feed resources');
                }

                $token = $this->getToken($tenant, $interactive);

                $start = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
                $startFormatted = $start->format('c');

                $startPlus1Hour = (clone $start)->add(new \DateInterval('PT1H'))->setTimezone(new \DateTimeZone('UTC'));

                // Get resources that are watched for availability.
                $watchedResources = $this->interactiveSlideCache->get(self::CACHE_KEY_RESOURCES, fn () => []);

                // Add resource to watchedResources, if not in list.
                if (!in_array($resource, $watchedResources)) {
                    $this->interactiveSlideCache->delete(self::CACHE_KEY_RESOURCES);

                    $watchedResources[] = $resource;
                    $this->interactiveSlideCache->get(self::CACHE_KEY_RESOURCES, fn () => $watchedResources);
                }

                $schedules = $this->getBusyIntervals($token, $watchedResources, $start, $startPlus1Hour);

                $result = [];

                // Refresh entries for all watched resources.
                foreach ($watchedResources as $watchResource) {
                    $entry = $this->createEntry($watchResource, $schedules[$watchResource], $startFormatted, $start);

                    if ($watchResource == $resource) {
                        $result = $entry;
                    } else {
                        // Refresh cache entry for resources in watch list that are not handled in current request.
                        $this->interactiveSlideCache->delete(self::CACHE_KEY_OPTIONS_PREFIX.$watchResource);
                        $this->interactiveSlideCache->get(self::CACHE_KEY_OPTIONS_PREFIX.$watchResource,
                            function (CacheItemInterface $item) use ($entry) {
                                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_OPTIONS));

                                return $entry;
                            }
                        );
                    }
                }

                return $result;
            }
        );
    }

    private function createEntry(string $resource, array $schedules, string $startFormatted, \DateTime $start): array
    {
        $entry = [
            'resource' => $resource,
            'from' => $startFormatted,
            'options' => [],
        ];

        foreach (self::DURATIONS as $durationMinutes) {
            $startPlus = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));

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
     * @throws \Throwable
     */
    private function quickBook(Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $resource = (string) $this->getValueFromInterval('resource', $interactionRequest);
        $durationMinutes = $this->getValueFromInterval('durationMinutes', $interactionRequest);

        $now = new \DateTime();

        // Make sure that booking requests are not spammed.
        $lastRequestDateTime = $this->interactiveSlideCache->get(
            self::CACHE_PREFIX_SPAM_PROTECT_PREFIX.$slide->getId(),
            function (CacheItemInterface $item) use ($now): \DateTime {
                $item->expiresAfter(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_SPAM_PROTECT));

                return $now;
            }
        );

        if ($lastRequestDateTime->add(new \DateInterval(self::CACHE_LIFETIME_QUICK_BOOK_SPAM_PROTECT)) > $now) {
            throw new ServiceUnavailableHttpException(60);
        }

        /** @var User|ScreenUser $activeUser */
        $activeUser = $this->security->getUser();
        $tenant = $activeUser->getActiveTenant();

        $interactive = $this->interactiveService->getInteractiveSlide($tenant, $interactionRequest->implementationClass);

        if (null === $interactive) {
            throw new BadRequestHttpException('Interactive not found');
        }

        // Optional limiting of available resources.
        $this->checkPermission($interactive, $resource);

        $feed = $slide->getFeed();

        if (null === $feed) {
            throw new BadRequestHttpException('Slide.feed not set.');
        }

        if (!in_array($resource, $feed->getConfiguration()['resources'] ?? [])) {
            throw new BadRequestHttpException('Resource not in feed resources');
        }

        $token = $this->getToken($tenant, $interactive);

        $configuration = $interactive->getConfiguration();

        if (null === $configuration) {
            throw new BadRequestHttpException('InteractiveNoConfiguration');
        }

        $username = $this->keyValueService->getValue($configuration['username']);

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
        $startPlusDuration = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));

        // Make sure interval is free.
        $busyIntervals = $this->getBusyIntervals($token, [$resource], $start, $startPlusDuration);
        if (count($busyIntervals[$resource]) > 0) {
            throw new ConflictHttpException('Interval booked already');
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

        return ['status' => $status, 'interval' => [
            'from' => $start->format('c'),
            'to' => $startPlusDuration->format('c'),
        ]];
    }

    /**
     * @see https://docs.microsoft.com/en-us/graph/api/calendar-getschedule?view=graph-rest-1.0&tabs=http
     *
     * @throws \Throwable
     */
    public function getBusyIntervals(string $token, array $resources, \DateTime $startTime, \DateTime $endTime): array
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

        $response = $this->client->request('POST', self::ENDPOINT.'/me/calendar/getSchedule', [
            'headers' => $this->getHeaders($token),
            'body' => json_encode($body),
        ]);

        $data = $response->toArray();

        $scheduleData = $data['value'];

        $result = [];

        foreach ($scheduleData as $schedule) {
            $scheduleId = $schedule['scheduleId'];
            $result[$scheduleId] = [];
            foreach ($schedule['scheduleItems'] as $scheduleItem) {
                $eventStartArray = $scheduleItem['start'];
                $eventEndArray = $scheduleItem['end'];

                $start = new \DateTime($eventStartArray['dateTime'], new \DateTimeZone($eventStartArray['timeZone']));
                $end = new \DateTime($eventEndArray['dateTime'], new \DateTimeZone($eventStartArray['timeZone']));

                $result[$scheduleId][] = [
                    'startTime' => $start,
                    'endTime' => $end,
                ];
            }
        }

        return $result;
    }

    public function intervalFree(array $schedule, \DateTime $from, \DateTime $to): bool
    {
        foreach ($schedule as $scheduleEntry) {
            if (!($scheduleEntry['startTime'] > $to || $scheduleEntry['endTime'] < $from)) {
                return false;
            }
        }

        return true;
    }

    private function getValueFromInterval(string $key, InteractionSlideRequest $interactionRequest): string|int
    {
        $interval = $interactionRequest->data['interval'] ?? null;

        if (null === $interval) {
            throw new BadRequestHttpException('interval not set.');
        }

        $value = $interval[$key] ?? null;

        if (null === $value) {
            throw new BadRequestHttpException("interval.'.$key.' not set.");
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

    private function checkPermission(InteractiveSlide $interactive, string $resource): void
    {
        $configuration = $interactive->getConfiguration();
        // Optional limiting of available resources.
        if (null !== $configuration && !empty($configuration['resourceEndpoint'])) {
            $allowedResources = $this->getAllowedResources($interactive);

            if (!in_array($resource, $allowedResources)) {
                throw new \Exception('Not allowed');
            }
        }
    }

    private function getAllowedResources(InteractiveSlide $interactive): array
    {
        return $this->interactiveSlideCache->get(self::CACHE_ALLOWED_RESOURCES_PREFIX.$interactive->getId(), function (CacheItemInterface $item) use ($interactive) {
            $item->expiresAfter(60 * 60);

            $configuration = $interactive->getConfiguration();

            $key = $configuration['resourceEndpoint'] ?? null;

            if (null === $key) {
                throw new \Exception('resourceEndpoint not set');
            }

            $resourceEndpoint = $this->keyValueService->getValue($key);

            if (null === $resourceEndpoint) {
                throw new \Exception('resourceEndpoint value not set');
            }

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
