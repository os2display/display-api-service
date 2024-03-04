<?php

declare(strict_types=1);

namespace App\Interactive;

use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveException;
use App\Service\InteractiveService;
use App\Service\KeyVaultService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Timezone;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interactive slide that allows for performing quick bookings of resources.
 *
 * Only resources attached to the slide through slide.feed.configuration.resources can be booked from the slide.
 */
class MicrosoftGraphQuickBook implements InteractiveInterface
{
    private const ACTION_GET_QUICK_BOOK_OPTIONS = 'ACTION_GET_QUICK_BOOK_OPTIONS';
    private const ACTION_QUICK_BOOK = 'ACTION_QUICK_BOOK';
    private const ENDPOINT = 'https://graph.microsoft.com/v1.0';
    private const LOGIN_ENDPOINT = 'https://login.microsoftonline.com/';
    private const OAUTH_PATH = '/oauth2/v2.0/token';
    private const SCOPE = 'https://graph.microsoft.com/.default';
    private const GRANT_TYPE = 'password';

    // see https://docs.microsoft.com/en-us/graph/api/resources/datetimetimezone?view=graph-rest-1.0
    // example 2019-03-15T09:00:00
    public const GRAPH_DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly InteractiveService $interactiveService,
        private readonly Security $security,
        private readonly HttpClientInterface $client,
        private readonly KeyVaultService $keyValueService,
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

    public function performAction(UserInterface $user, Slide $slide, InteractionRequest $interactionRequest): array
    {
        return match ($interactionRequest->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($slide, $interactionRequest),
            self::ACTION_QUICK_BOOK => $this->quickBook($slide, $interactionRequest),
            default => throw new InteractiveException('Action not allowed'),
        };
    }

    /**
     * @throws \Throwable
     */
    private function authenticate(array $configuration): string
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

        $data = $response->toArray();

        // TODO: cache response.

        return $data['access_token'];
    }

    /**
     * @throws \Throwable
     */
    private function getQuickBookOptions(Slide $slide, InteractionRequest $interactionRequest): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();

        $interactive = $this->interactiveService->getInteractive($tenant, $interactionRequest->implementationClass);

        // TODO: Custom exceptions.

        if (null === $interactive) {
            throw new \Exception('InteractiveNotFound');
        }

        $configuration = $interactive->getConfiguration();

        if (null === $configuration) {
            throw new \Exception('InteractiveNoConfiguration');
        }

        $token = $this->authenticate($configuration);

        $start = (new \DateTime())->add(new \DateInterval('PT1M'))->setTimezone(new \DateTimeZone('UTC'));
        $startPlus15Minutes = (clone $start)->add(new \DateInterval('PT15M'))->setTimezone(new \DateTimeZone('UTC'));
        $startPlus30Minutes = (clone $start)->add(new \DateInterval('PT30M'))->setTimezone(new \DateTimeZone('UTC'));
        $startPlus1Hour = (clone $start)->add(new \DateInterval('PT1H'))->setTimezone(new \DateTimeZone('UTC'));

        $schedule = $this->getBusyIntervals($token, $interactionRequest->data['resource'], $start, $startPlus1Hour);

        $startFormatted = $start->format('c');
        $startPlus15MinutesFormatted = $startPlus15Minutes->format('c');
        $startPlus30MinutesFormatted = $startPlus30Minutes->format('c');
        $startPlus1HourFormatted = $startPlus1Hour->format('c');

        return [
            [
                'title' => '15 min',
                'from' => $startFormatted,
                'to' => $startPlus15MinutesFormatted,
                'available' => $this->intervalFree($schedule, $start, $startPlus15Minutes),
            ],
            [
                'title' => '30 min',
                'from' => $startFormatted,
                'to' => $startPlus30MinutesFormatted,
                'available' => $this->intervalFree($schedule, $start, $startPlus30Minutes),
            ],
            [
                'title' => '60 min',
                'from' => $startFormatted,
                'to' => $startPlus1HourFormatted,
                'available' => $this->intervalFree($schedule, $start, $startPlus1Hour),
            ],
        ];
    }

    private function quickBook(Slide $slide, InteractionRequest $interaction): array
    {
        return ['test3' => 'test4'];
    }

    /**
     * @see https://docs.microsoft.com/en-us/graph/api/calendar-getschedule?view=graph-rest-1.0&tabs=http
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
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);

        $data = $response->toArray();

        $scheduleData = $data['value'];

        $result = [];

        foreach ($scheduleData as $schedule) {
            foreach ($schedule['scheduleItems'] as $scheduleItem) {
                $eventStartArray = $scheduleItem['start'];
                $eventEndArray = $scheduleItem['end'];

                $p = 1;

                $result[] = [
                    'startTime' => $scheduleItem['start'],
                    'endTime' => $scheduleItem['end'],
                ];
            }
        }

        return $result;
    }

    private function intervalFree(array $schedule, \DateTime $from , \DateTime $to): bool
    {
        return false;
    }
}
