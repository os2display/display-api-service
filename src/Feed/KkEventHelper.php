<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Poster\Place;
use App\Feed\OutputModel\Poster\Poster;
use App\Feed\OutputModel\Poster\PosterOption;
use RRule\RRule;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HTTP client + KK External Entities Event payload mapping.
 *
 * @see kkevent-external-entities-README.pdf for the source field model.
 */
class KkEventHelper
{
    private const string DEFAULT_TIMEZONE = 'Europe/Copenhagen';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {}

    /**
     * @param array<string, mixed>|null $queryParams
     *
     * @return array<mixed>
     */
    public function request(FeedSource $feedSource, string $path, ?array $queryParams = null, ?string $entityUuid = null): array
    {
        $secrets = $feedSource->getSecrets();

        if (!isset($secrets['host']) || '' === $secrets['host']) {
            throw new \RuntimeException('KkEventFeedType: Missing host secret.');
        }

        $host = rtrim((string) $secrets['host'], '/');
        $apikey = $secrets['apikey'] ?? null;

        $options = [];
        if (is_string($apikey) && '' !== $apikey) {
            $options['headers']['X-Api-Key'] = $apikey;
        }
        if (null !== $queryParams) {
            $options['query'] = $queryParams;
        }

        $url = $host.'/'.$path;
        if (null !== $entityUuid) {
            $url .= '/'.$entityUuid;
        }

        $response = $this->client->request('GET', $url, $options);
        $content = $response->getContent();
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * Extract a list of items from a possibly-wrapped JSON response.
     *
     * Supports flat arrays, hydra:member, data, and items wrappers, plus
     * single-entity responses returned as an associative array.
     *
     * @param array<mixed> $response
     *
     * @return array<mixed>
     */
    public function extractItems(array $response): array
    {
        if (isset($response['hydra:member']) && is_array($response['hydra:member'])) {
            return $response['hydra:member'];
        }
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if (isset($response['items']) && is_array($response['items'])) {
            return $response['items'];
        }
        if (array_is_list($response)) {
            return $response;
        }

        return [$response];
    }

    /**
     * @param array<mixed> $response
     */
    public function extractTotalItems(array $response, int $defaultCount): int
    {
        if (isset($response['hydra:totalItems'])) {
            return (int) $response['hydra:totalItems'];
        }
        if (isset($response['total'])) {
            return (int) $response['total'];
        }

        return $defaultCount;
    }

    /**
     * @param array<string, mixed> $event
     */
    public function mapEventToPoster(array $event): ?Poster
    {
        if (!isset($event['uuid'], $event['title'])) {
            return null;
        }

        [$start, $end] = $this->resolveNextOccurrence($event);

        if (null === $start || null === $end) {
            return null;
        }

        $url = $event['url'] ?? null;
        $baseUrl = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        $image = $event['image'] ?? null;

        return new Poster(
            (string) $event['uuid'],
            (string) $event['uuid'],
            $event['ticket_url'] ?? null,
            $event['body'] ?? null,
            $event['teaser'] ?? null,
            (string) $event['title'],
            is_string($url) ? $url : null,
            is_string($baseUrl) ? $baseUrl : null,
            is_string($image) ? $image : null,
            is_string($image) ? $image : null,
            $start->format(\DATE_ATOM),
            $end->format(\DATE_ATOM),
            $this->formatTicketPriceRange($event['ticket_categories'] ?? []),
            null,
            null,
            $this->mapContactToPlace($event['contact'] ?? null),
        );
    }

    /**
     * Compute the next upcoming occurrence start/end for an event.
     *
     * Returns [\DateTimeImmutable|null, \DateTimeImmutable|null] for [start, end].
     *
     * @param array<string, mixed> $event
     *
     * @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable}
     */
    private function resolveNextOccurrence(array $event): array
    {
        $start = $this->parseDate($event['start_date'] ?? null);
        $end = $this->parseDate($event['end_date'] ?? null);

        if (null === $start || null === $end) {
            return [null, null];
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::DEFAULT_TIMEZONE));
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $scheduleType = $event['schedule_type'] ?? 'single';
        $schedule = is_array($event['schedule'] ?? null) ? $event['schedule'] : [];
        $rrule = $schedule['rrule'] ?? null;
        $rdate = $schedule['rdate'] ?? null;
        $exdate = $schedule['exdate'] ?? null;

        $excluded = $this->parseRfc5545List($exdate);

        if ('repeated' === $scheduleType && is_string($rrule) && '' !== $rrule) {
            $next = $this->findNextRRuleOccurrence($rrule, $start, $excluded, $now);
            if (null !== $next) {
                return [$next, $next->setTimestamp($next->getTimestamp() + $duration)];
            }
        }

        $candidates = [$start];
        foreach ($this->parseRfc5545List($rdate) as $rdateValue) {
            $candidates[] = $rdateValue;
        }
        sort($candidates);

        $excludedKeys = [];
        foreach ($excluded as $exDateValue) {
            $excludedKeys[$exDateValue->format(\DATE_ATOM)] = true;
        }

        foreach ($candidates as $candidate) {
            if (isset($excludedKeys[$candidate->format(\DATE_ATOM)])) {
                continue;
            }
            $candidateEnd = $candidate->setTimestamp($candidate->getTimestamp() + $duration);
            if ($candidateEnd >= $now) {
                return [$candidate, $candidateEnd];
            }
        }

        return [null, null];
    }

    /**
     * @param array<\DateTimeImmutable> $excluded
     */
    private function findNextRRuleOccurrence(string $rrule, \DateTimeImmutable $dtstart, array $excluded, \DateTimeImmutable $now): ?\DateTimeImmutable
    {
        try {
            $rule = new RRule($rrule, $dtstart);
        } catch (\Throwable) {
            return null;
        }

        $excludedKeys = [];
        foreach ($excluded as $excludedDate) {
            $excludedKeys[$excludedDate->format(\DATE_ATOM)] = true;
        }

        $count = 0;
        foreach ($rule as $occurrence) {
            ++$count;
            if ($count > 1000) {
                break;
            }
            $immutable = \DateTimeImmutable::createFromInterface($occurrence);
            if ($immutable < $now) {
                continue;
            }
            if (isset($excludedKeys[$immutable->format(\DATE_ATOM)])) {
                continue;
            }

            return $immutable;
        }

        return null;
    }

    /**
     * @return array<\DateTimeImmutable>
     */
    private function parseRfc5545List(mixed $list): array
    {
        if (!is_string($list) || '' === $list) {
            return [];
        }
        $result = [];
        foreach (explode(',', $list) as $value) {
            $parsed = $this->parseDate(trim($value));
            if (null !== $parsed) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapContactToPlace(mixed $contact): ?Place
    {
        if (!is_array($contact)) {
            return null;
        }
        $name = $contact['location'] ?? $contact['name'] ?? null;
        if (!is_string($name) || '' === $name) {
            return null;
        }

        return new Place(
            $name,
            isset($contact['street_and_num']) ? (string) $contact['street_and_num'] : null,
            isset($contact['zip']) ? (string) $contact['zip'] : null,
            isset($contact['city']) ? (string) $contact['city'] : null,
            null,
            isset($contact['phone']) ? (string) $contact['phone'] : null,
        );
    }

    /**
     * Format the price range from KK ticket categories.
     *
     * Per the KK external entities spec: amounts are stored in øre and divided
     * by 100 (truncated) to get DKK. All-zero / empty → free; one category →
     * single price; multiple → "title: amount kr." comma-joined.
     */
    public function formatTicketPriceRange(mixed $categories): ?string
    {
        if (!is_array($categories) || 0 === count($categories)) {
            return 'Gratis';
        }

        $items = [];
        $allZero = true;
        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }
            $kr = (int) ((isset($category['amount']) ? (int) $category['amount'] : 0) / 100);
            $title = isset($category['title']) && is_string($category['title']) ? $category['title'] : null;
            $items[] = ['kr' => $kr, 'title' => $title];
            if ($kr > 0) {
                $allZero = false;
            }
        }

        if ($allZero || 0 === count($items)) {
            return 'Gratis';
        }

        if (1 === count($items)) {
            return $items[0]['kr'].' kr.';
        }

        $parts = [];
        foreach ($items as $item) {
            $parts[] = (null !== $item['title'] ? $item['title'].': ' : '').$item['kr'].' kr.';
        }

        return implode(', ', $parts);
    }

    public function toEntityResult(string $entityType, mixed $entity): mixed
    {
        if ('events' === $entityType) {
            return is_array($entity) ? $this->mapEventToPoster($entity) : null;
        }

        if (in_array($entityType, ['tags', 'categories', 'target_groups', 'district'], true)) {
            $normalized = is_array($entity) ? $entity : ['name' => (string) $entity];

            return $this->toPosterOption($normalized);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $entity
     */
    public function toPosterOption(array $entity): PosterOption
    {
        $name = $entity['name'] ?? $entity['title'] ?? '';
        $value = $entity['uuid'] ?? $entity['name'] ?? $name;

        return new PosterOption((string) $name, (string) $value);
    }
}
