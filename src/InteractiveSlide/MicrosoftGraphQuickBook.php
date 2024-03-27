<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

use App\Entity\Tenant;
use App\Entity\Tenant\InteractiveSlide;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveSlideException;
use App\Service\InteractiveSlideService;
use App\Service\KeyVaultService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interactive slide that allows for performing quick bookings of resources.
 *
 * Only resources attached to the slide through slide.feed.configuration.resources can be booked from the slide.
 */
class MicrosoftGraphQuickBook implements InteractiveSlideInterface
{
    private const string ACTION_GET_QUICK_BOOK_OPTIONS = 'ACTION_GET_QUICK_BOOK_OPTIONS';
    private const string ACTION_QUICK_BOOK = 'ACTION_QUICK_BOOK';
    private const string ENDPOINT = 'https://graph.microsoft.com/v1.0';
    private const string LOGIN_ENDPOINT = 'https://login.microsoftonline.com/';
    private const string OAUTH_PATH = '/oauth2/v2.0/token';
    private const string SCOPE = 'https://graph.microsoft.com/.default';
    private const string GRANT_TYPE = 'password';
    private const string CACHE_PREFIX = 'MSGraphQuickBook';
    private const string BOOKING_TITLE = 'Straksbooking';

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
        ];
    }

    public function performAction(UserInterface $user, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        return match ($interactionRequest->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($slide, $interactionRequest),
            self::ACTION_QUICK_BOOK => $this->quickBook($slide, $interactionRequest),
            default => throw new InteractiveSlideException('Action not allowed'),
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
            throw new \Exception('tenantId, clientId, username, password must all be set.');
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
            throw new \Exception('InteractiveNoConfiguration');
        }

        return $this->interactiveSlideCache->get(
            self::CACHE_PREFIX . '-token-'.$tenant->getTenantKey(),
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
        // TODO: Add caching to avoid spamming Microsoft Graph.

        /** @var User $user */
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();

        $interactive = $this->interactiveService->getInteractive($tenant, $interactionRequest->implementationClass);

        if (null === $interactive) {
            throw new \Exception('InteractiveNotFound');
        }

        $feed = $slide->getFeed();

        if (null === $feed) {
            throw new \Exception('Slide.feed not set.');
        }

        if (!in_array($interactionRequest->data['resource'] ?? '', $feed->getConfiguration()['resources'] ?? [])) {
            throw new \Exception('Resource not in feed resources');
        }

        $token = $this->getToken($tenant, $interactive);

        $start = (new \DateTime())->add(new \DateInterval('PT1M'))->setTimezone(new \DateTimeZone('UTC'));
        $startFormatted = $start->format('c');

        $startPlus1Hour = (clone $start)->add(new \DateInterval('PT1H'))->setTimezone(new \DateTimeZone('UTC'));

        $schedule = $this->getBusyIntervals($token, $interactionRequest->data['resource'], $start, $startPlus1Hour);

        $result = [];

        foreach ([15,30,60] as $durationMinutes) {
            $startPlus = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));
            $startPlusFormatted = $startPlus->format('c');

            if ($this->intervalFree($schedule, $start, $startPlus)) {
                $result[] = [
                    'durationMinutes' => $durationMinutes,
                    'resource' => $interactionRequest->data['resource'],
                    'from' => $startFormatted,
                    'to' => $startPlusFormatted,
                ];
            }
        }

        return $result;
    }

    /**
     * @throws \Throwable
     */
    private function quickBook(Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $resource = $this->getValueFromInterval('resource', $interactionRequest);
        $durationMinutes = $this->getValueFromInterval('durationMinutes', $interactionRequest);

        $now = new \DateTime();

        // Make sure that booking requests are not spammed.
        $lastRequestDateTime = $this->interactiveSlideCache->get(
            self::CACHE_PREFIX . "-sp-".$slide->getId(),
            function (CacheItemInterface $item) use ($now): \DateTime {
                $item->expiresAfter(new \DateInterval('PT1M'));
                return $now;
            }
        );

        if (($lastRequestDateTime)->add(new \DateInterval('PT1M')) > $now) {
            throw new ServiceUnavailableHttpException(60);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();

        $interactive = $this->interactiveService->getInteractive($tenant, $interactionRequest->implementationClass);

        if (null === $interactive) {
            throw new \Exception('InteractiveNotFound');
        }

        $feed = $slide->getFeed();

        if (null === $feed) {
            throw new \Exception('Slide.feed not set.');
        }

        if (!in_array($resource, $feed->getConfiguration()['resources'] ?? [])) {
            throw new \Exception('Resource not in feed resources');
        }

        $token = $this->getToken($tenant, $interactive);

        $configuration = $interactive->getConfiguration();

        if (null === $configuration) {
            throw new \Exception('InteractiveNoConfiguration');
        }

        $username = $this->keyValueService->getValue($configuration['username']);

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));;
        $startPlusDuration = (clone $start)->add(new \DateInterval('PT'.$durationMinutes.'M'))->setTimezone(new \DateTimeZone('UTC'));;

        // Make sure interval is free.
        if (count($this->getBusyIntervals($token, $resource, $start, $startPlusDuration)) > 0) {
            throw new ConflictHttpException("Interval booked already");
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

        return ['status' => $status, 'interval' => $interactionRequest->data];
    }

    /**
     * @see https://docs.microsoft.com/en-us/graph/api/calendar-getschedule?view=graph-rest-1.0&tabs=http
     * @throws \Throwable
     */
    public function getBusyIntervals(string $token, string $resource, \DateTime $startTime, \DateTime $endTime): array
    {
        $body = [
            'schedules' => [$resource],
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
            foreach ($schedule['scheduleItems'] as $scheduleItem) {
                $eventStartArray = $scheduleItem['start'];
                $eventEndArray = $scheduleItem['end'];

                $start = new \DateTime($eventStartArray['dateTime'], new \DateTimeZone($eventStartArray['timeZone']));
                $end = new \DateTime($eventEndArray['dateTime'], new \DateTimeZone($eventStartArray['timeZone']));

                $result[] = [
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

        if ($interval === null) {
            throw new \Exception("interval not set.");
        }

        $value = $interval[$key] ?? null;

        if ($value === null) {
            throw new \Exception("interval.'.$key.' not set.");
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
}
