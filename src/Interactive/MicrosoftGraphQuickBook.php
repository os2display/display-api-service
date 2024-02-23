<?php

namespace App\Interactive;

use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveException;
use App\Service\InteractiveService;
use Symfony\Bundle\SecurityBundle\Security;
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
    private const MICROSOFT_GRAPH_ENDPOINT = 'https://graph.microsoft.com/v1.0';

    // see https://docs.microsoft.com/en-us/graph/api/resources/datetimetimezone?view=graph-rest-1.0
    // example 2019-03-15T09:00:00
    public const GRAPH_DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly InteractiveService $interactiveService,
        private readonly Security $security,
        private readonly HttpClientInterface $client,
    )
    {
    }

    public function getConfigOptions(): array
    {
        return [
            'tenantId' => [
                'required' => true,
                'description' => 'The tenant id of the App'
            ],
            'clientId' => [
                'required' => true,
                'description' => 'The client id of the App'
            ],
            'username' => [
                'required' => true,
                'description' => 'The Microsoft Graph username that should perform the action.',
            ],
            'password' => [
                'required' => true,
                'description' => 'The password of the user.',
            ],
        ];
    }

    /**
     * @throws InteractiveException
     */
    public function performAction(Slide $slide, InteractionRequest $interactionRequest): array
    {
        return match ($interactionRequest->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($slide, $interactionRequest),
            self::ACTION_QUICK_BOOK => $this->quickBook($slide, $interactionRequest),
            default => throw new InteractiveException("Action not allowed"),
        };
    }

    /**
     * @throws \Throwable
     */
    private function authenticate(array $configuration): string
    {
        $url = 'https://login.microsoftonline.com/'.$configuration['tenantId'].'/oauth2/v2.0/token';

        $response = $this->client->request("POST", $url, [
            'body' => [
                'client_id' => $configuration['clientId'],
                'scope' => 'https://graph.microsoft.com/.default',
                'username' => $configuration['username'],
                'password' => $configuration['password'],
                'grant_type' => 'password',
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

        if ($interactive === null) {
            throw new \Exception("InteractiveNotFound");
        }

        $configuration = $interactive->getConfiguration();

        if ($configuration === null) {
            throw new \Exception("InteractiveNoConfiguration");
        }

        $token = $this->authenticate($configuration);

        $now = new \DateTime();
        $nowPlusOneHour = (new \DateTime())->add(new \DateInterval('PT1H'));

        $schedule = $this->getBusyIntervals($token, $interactionRequest->data['resource'], $now, $nowPlusOneHour);

        print_r($schedule);die();

        return ["test1" => "test2"];
    }

    private function quickBook(Slide $slide, InteractionRequest $interaction): array
    {
        return ["test3" => "test4"];
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

        $response = $this->client->request("POST", self::MICROSOFT_GRAPH_ENDPOINT."/me/calendar/getSchedule", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);

        $data = $response->toArray();

        $scheduleData = $data['value'];

        $result = [];

        foreach ($scheduleData as $schedule) {
            $scheduleResult = [];

            foreach ($schedule['scheduleItems'] as $scheduleItem) {
                $scheduleResult[] = [
                    'startTime' => $scheduleItem['start'],
                    'endTime' => $scheduleItem['end'],
                ];
            }

            $result[$schedule['scheduleId']] = $scheduleResult;
        }

        return $result;
    }
}
