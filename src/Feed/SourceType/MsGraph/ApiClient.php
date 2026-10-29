<?php

declare(strict_types=1);

namespace App\Feed\SourceType\MsGraph;

use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Calendar\CalendarEvent;
use App\Feed\OutputModel\Calendar\Location;
use App\Feed\OutputModel\Calendar\Resource;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Thin Microsoft Graph client for room (resource mailbox) calendars.
 *
 * Authenticates with the OAuth2 client credentials flow (app-only), i.e. the
 * app registration acts as itself — there is no signed-in user, so the
 * required Graph permissions are *application* permissions with admin consent:
 *
 * - `Place.Read.All`    — list room lists and rooms.
 * - `Calendars.Read`    — read the room mailbox calendars. Should be narrowed
 *                         to the relevant mailboxes with an Exchange
 *                         application access policy (`New-ApplicationAccessPolicy`),
 *                         otherwise the app can read *every* mailbox in the tenant.
 *
 * All reads are cached in the dedicated `ms.graph.cache` pool: tokens until
 * shortly before they expire, places for an hour (they rarely change), and
 * calendar views for the TTL the editor picks per feed. Graph throttles per
 * app/mailbox, so caching is what keeps a wall of screens from hitting 429.
 */
class ApiClient
{
    public const string GRAPH_ENDPOINT = 'https://graph.microsoft.com/v1.0';
    public const string GRAPH_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    private const string LOGIN_ENDPOINT = 'https://login.microsoftonline.com/';
    private const string OAUTH_PATH = '/oauth2/v2.0/token';
    private const string SCOPE = 'https://graph.microsoft.com/.default';
    private const string GRANT_TYPE = 'client_credentials';

    private const string CACHE_KEY_TOKEN_PREFIX = 'msgraph-token-';
    private const string CACHE_KEY_ROOM_LISTS_PREFIX = 'msgraph-roomlists-v2-';
    private const string CACHE_KEY_ROOMS_PREFIX = 'msgraph-rooms-v2-';
    private const string CACHE_KEY_EVENTS_PREFIX = 'msgraph-events-';

    /** Tokens are valid for ~1 hour; renew a little early to avoid races. */
    private const int TOKEN_EXPIRE_MARGIN_SECONDS = 300;
    private const int TOKEN_MIN_TTL_SECONDS = 60;
    private const int PLACES_CACHE_TTL_SECONDS = 3600;
    private const int PAGE_SIZE = 100;

    /** Hard stop on `@odata.nextLink` following, so a broken page cursor cannot loop forever. */
    private const int MAX_PAGES = 20;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $msGraphCache,
    ) {}

    /**
     * List the room lists ("locations") of the tenant.
     *
     * @return array<string, Location> keyed by room list email address
     *
     * @throws MsGraphException
     */
    public function getRoomLists(FeedSource $feedSource): array
    {
        $secrets = new SecretsDTO($feedSource);

        /** @var array<string, Location> $roomLists */
        $roomLists = $this->msGraphCache->get(
            self::CACHE_KEY_ROOM_LISTS_PREFIX.$secrets->getCredentialsHash(),
            function (ItemInterface $item) use ($feedSource): array {
                $item->expiresAfter(self::PLACES_CACHE_TTL_SECONDS);

                $locations = [];

                foreach ($this->requestAllPages($feedSource, '/places/microsoft.graph.roomlist', offsetPagination: true) as $entry) {
                    $email = $this->getPlaceEmail($entry);

                    if (null === $email) {
                        continue;
                    }

                    $displayName = $entry['displayName'] ?? null;

                    $locations[$email] = new Location($email, is_string($displayName) && '' !== $displayName ? $displayName : $email);
                }

                return $locations;
            },
        );

        return $roomLists;
    }

    /**
     * List rooms, either all of them or only the ones in a given room list.
     *
     * @return array<string, \App\Feed\OutputModel\Calendar\Resource> keyed by room email address
     *
     * @throws MsGraphException
     */
    public function getRooms(FeedSource $feedSource, ?string $roomListEmail = null): array
    {
        $secrets = new SecretsDTO($feedSource);

        $path = null === $roomListEmail
            ? '/places/microsoft.graph.room'
            : '/places/'.rawurlencode($roomListEmail).'/microsoft.graph.rooms';

        $cacheKey = self::CACHE_KEY_ROOMS_PREFIX.$secrets->getCredentialsHash().'-'.(null === $roomListEmail ? 'all' : $this->hash($roomListEmail));

        /** @var array<string, \App\Feed\OutputModel\Calendar\Resource> $rooms */
        $rooms = $this->msGraphCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($feedSource, $path, $roomListEmail): array {
                $item->expiresAfter(self::PLACES_CACHE_TTL_SECONDS);

                $rooms = [];

                foreach ($this->requestAllPages($feedSource, $path, offsetPagination: true) as $entry) {
                    $email = $this->getPlaceEmail($entry);

                    if (null === $email) {
                        continue;
                    }

                    $displayName = $entry['displayName'] ?? null;

                    $rooms[$email] = new Resource(
                        $email,
                        $email,
                        $roomListEmail ?? '',
                        is_string($displayName) && '' !== $displayName ? $displayName : $email,
                    );
                }

                return $rooms;
            },
        );

        return $rooms;
    }

    /**
     * Fetch the calendar view (expanded recurrences) of a single room mailbox.
     *
     * @param \DateTimeImmutable $from              window start (inclusive)
     * @param \DateTimeImmutable $to                window end (exclusive)
     * @param int                $cacheTtlSeconds   how long the result may be served from cache
     * @param string             $defaultEventTitle used when the room mailbox has stripped the subject
     *
     * @return list<CalendarEvent>
     *
     * @throws MsGraphException
     */
    public function getEvents(
        FeedSource $feedSource,
        string $roomEmail,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $cacheTtlSeconds,
        string $defaultEventTitle,
    ): array {
        $secrets = new SecretsDTO($feedSource);

        $cacheKey = sprintf(
            '%s%s-%s-%s-%s',
            self::CACHE_KEY_EVENTS_PREFIX,
            $secrets->getCredentialsHash(),
            $this->hash($roomEmail),
            $from->setTimezone(new \DateTimeZone('UTC'))->format('YmdHi'),
            $to->setTimezone(new \DateTimeZone('UTC'))->format('YmdHi'),
        );

        /** @var list<CalendarEvent> $events */
        $events = $this->msGraphCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($feedSource, $roomEmail, $from, $to, $cacheTtlSeconds, $defaultEventTitle): array {
                $item->expiresAfter($cacheTtlSeconds);

                $entries = $this->requestAllPages(
                    $feedSource,
                    '/users/'.rawurlencode($roomEmail).'/calendarView',
                    [
                        'startDateTime' => $from->setTimezone(new \DateTimeZone('UTC'))->format(self::GRAPH_DATE_FORMAT),
                        'endDateTime' => $to->setTimezone(new \DateTimeZone('UTC'))->format(self::GRAPH_DATE_FORMAT),
                        '$select' => 'id,subject,start,end,isCancelled',
                        '$orderby' => 'start/dateTime',
                        '$top' => self::PAGE_SIZE,
                    ],
                    // Ask Graph to return all times in UTC so they can be read
                    // without guessing the mailbox' own time zone.
                    ['Prefer' => 'outlook.timezone="UTC"'],
                );

                $events = [];

                foreach ($entries as $entry) {
                    if (true === ($entry['isCancelled'] ?? false)) {
                        continue;
                    }

                    $start = $this->parseGraphDateTime($entry['start'] ?? null);
                    $end = $this->parseGraphDateTime($entry['end'] ?? null);

                    if (null === $start || null === $end) {
                        continue;
                    }

                    $subject = $entry['subject'] ?? null;
                    $title = is_string($subject) ? trim($subject) : '';

                    $events[] = new CalendarEvent(
                        Ulid::generate(),
                        '' !== $title ? $title : $defaultEventTitle,
                        $start->getTimestamp(),
                        $end->getTimestamp(),
                        $roomEmail,
                        $roomEmail,
                    );
                }

                return $events;
            },
        );

        return $events;
    }

    /**
     * Get an app-only access token for the feed source, cached until just before it expires.
     *
     * @throws MsGraphException
     */
    private function getToken(FeedSource $feedSource): string
    {
        $secrets = new SecretsDTO($feedSource);

        return $this->msGraphCache->get(
            self::CACHE_KEY_TOKEN_PREFIX.$secrets->getCredentialsHash(),
            function (ItemInterface $item) use ($secrets): string {
                try {
                    $response = $this->client->request('POST', self::LOGIN_ENDPOINT.$secrets->tenantId.self::OAUTH_PATH, [
                        'body' => [
                            'client_id' => $secrets->clientId,
                            'client_secret' => $secrets->clientSecret,
                            'scope' => self::SCOPE,
                            'grant_type' => self::GRANT_TYPE,
                        ],
                    ]);

                    $statusCode = $response->getStatusCode();
                    // Don't throw on non-2xx: the error body carries the reason.
                    $content = $response->getContent(false);
                } catch (\Throwable $throwable) {
                    throw new TokenException('MS Graph token request failed: '.$throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }

                try {
                    $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new TokenException(sprintf('MS Graph token response was not valid JSON (HTTP %d).', $statusCode), $statusCode, $exception);
                }

                if (!is_array($data)) {
                    throw new TokenException(sprintf('MS Graph token response was not an object (HTTP %d).', $statusCode), $statusCode);
                }

                $token = $data['access_token'] ?? null;

                if (200 !== $statusCode || !is_string($token) || '' === $token) {
                    // Only the short error code is propagated — the error
                    // description can echo request parameters.
                    $error = $data['error'] ?? null;

                    throw new TokenException(sprintf('MS Graph token request was rejected (HTTP %d): %s', $statusCode, is_string($error) ? $error : 'unknown_error'), $statusCode);
                }

                $expiresIn = (int) ($data['expires_in'] ?? 0);
                $item->expiresAfter(max(self::TOKEN_MIN_TTL_SECONDS, $expiresIn - self::TOKEN_EXPIRE_MARGIN_SECONDS));

                return $token;
            },
        );
    }

    /**
     * Perform a GET against Graph and follow `@odata.nextLink` until exhausted.
     *
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     *
     * @return list<array<string, mixed>>
     *
     * @throws MsGraphException
     */
    private function requestAllPages(FeedSource $feedSource, string $path, array $query = [], array $headers = [], bool $offsetPagination = false): array
    {
        $token = $this->getToken($feedSource);

        if ($offsetPagination) {
            $query['$top'] = self::PAGE_SIZE;
        }

        $url = self::GRAPH_ENDPOINT.$path;
        $results = [];
        $page = 0;

        while (null !== $url && $page < self::MAX_PAGES) {
            $options = [
                'headers' => array_merge([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ], $headers),
            ];

            // `@odata.nextLink` is a fully built URL — re-adding the query
            // would reset the paging cursor and loop over page one forever.
            if (0 === $page && [] !== $query) {
                $options['query'] = $query;
            }

            try {
                $data = $this->client->request('GET', $url, $options)->toArray();
            } catch (\Throwable $throwable) {
                throw new MsGraphException(sprintf('MS Graph request to "%s" failed: %s', $path, $throwable->getMessage()), (int) $throwable->getCode(), $throwable);
            }

            foreach ($data['value'] ?? [] as $entry) {
                if (is_array($entry)) {
                    $results[] = $entry;
                }
            }

            $next = $data['@odata.nextLink'] ?? null;
            $url = is_string($next) && '' !== $next ? $next : null;

            ++$page;

            // Places can omit nextLink even when more rooms exist.
            if (null === $url && $offsetPagination && self::PAGE_SIZE === count($data['value'] ?? [])) {
                $url = self::GRAPH_ENDPOINT.$path.'?'.http_build_query(array_merge($query, ['$skip' => $page * self::PAGE_SIZE]));
            }
        }

        if (null !== $url) {
            throw new MsGraphException('MS Graph pagination exceeded the page limit; refusing to return an incomplete result.');
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $place
     */
    private function getPlaceEmail(array $place): ?string
    {
        $email = $place['emailAddress'] ?? null;

        return is_string($email) && '' !== $email ? $email : null;
    }

    /**
     * Graph returns `{"dateTime": "2024-01-01T09:00:00.0000000", "timeZone": "UTC"}`
     * — the offset lives in the sibling `timeZone` property, not in the string.
     */
    private function parseGraphDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_array($value)) {
            return null;
        }

        $dateTime = $value['dateTime'] ?? null;
        $timeZone = $value['timeZone'] ?? null;

        if (!is_string($dateTime) || '' === $dateTime) {
            return null;
        }

        try {
            return new \DateTimeImmutable($dateTime, new \DateTimeZone(is_string($timeZone) && '' !== $timeZone ? $timeZone : 'UTC'));
        } catch (\Throwable) { // @phpstan-ignore logging.silentCatch (an unparsable date is a single malformed event; the caller drops it, and logging one line per bad entry would flood the feed channel)
            return null;
        }
    }

    private function hash(string $value): string
    {
        return substr(hash('sha256', $value), 0, 16);
    }
}
