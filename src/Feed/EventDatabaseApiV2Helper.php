<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\Poster\Event;
use App\Feed\OutputModel\Poster\ImageUrls;
use App\Feed\OutputModel\Poster\Occurrence;
use App\Feed\OutputModel\Poster\Organizer;
use App\Feed\OutputModel\Poster\Place;
use App\Feed\OutputModel\Poster\Poster;
use App\Feed\OutputModel\Poster\PosterOption;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EventDatabaseApiV2Helper
{
    final public const int REQUEST_TIMEOUT = 10;

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {}

    public function request(FeedSource $feedSource, string $entityType, ?array $queryParams = null, ?int $entityId = null): object
    {
        $secrets = $feedSource->getSecrets();

        if (!isset($secrets['host']) || !isset($secrets['apikey'])) {
            throw new \Exception('Missing host or apikey');
        }

        $host = $secrets['host'];
        $apikey = $secrets['apikey'];

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => [
                'X-Api-Key' => $apikey,
            ],
        ];

        if (null !== $queryParams) {
            $options['query'] = $queryParams;
        }

        $path = "$host/$entityType";

        if (null !== $entityId) {
            $path .= "/$entityId";
        }

        $response = $this->client->request(
            'GET',
            $path,
            $options
        );

        $content = $response->getContent();

        return json_decode($content, null, 512, JSON_THROW_ON_ERROR);
    }

    public function toEntityResult(string $entityType, object $entity): ?object
    {
        return match ($entityType) {
            'occurrences' => $this->mapOccurrenceToOutput($entity),
            'events' => $this->mapEventToOutput($entity),
            default => throw new \Exception('Unknown entity type.'),
        };
    }

    public function createPoster(?object $event = null, ?object $occurrence = null): ?Poster
    {
        if (null == $event || null == $occurrence) {
            return null;
        }

        $imageUrls = (object) ($event->imageUrls ?? []);
        $location = $event->location ?? null;
        $baseUrl = null !== $event->url ? parse_url($event->url, PHP_URL_HOST) : null;
        $place = null;

        if (null !== $location) {
            $place = new Place(
                $location->name ?? null,
                $location->streetAddress ?? null,
                $location->postalCode ?? null,
                $location->city ?? null,
                $location->image ?? null,
                $location->telephone ?? null,
            );
        }

        $organizer = isset($event->organizer?->name) ?
            new Organizer($event->organizer->name) : null;

        return new Poster(
            $event->entityId ?? null,
            $occurrence->entityId ?? null,
            $event->ticketUrl ?? null,
            $event->description ?? null,
            $event->excerpt ?? null,
            $event->title ?? null,
            $event->url ?? null,
            $baseUrl,
            $imageUrls->large ?? null,
            $imageUrls->small ?? null,
            $occurrence->start ?? null,
            $occurrence->end ?? null,
            $occurrence->ticketPriceRange ?? null,
            $occurrence->status ?? null,
            $organizer,
            $place,
        );
    }

    public function mapFirstOccurrenceToOutput(object $event): ?Poster
    {
        $occurrence = null;

        if (count($event->occurrences) > 0) {
            $occurrence = $event->occurrences[0];
        }

        return $this->createPoster($event, $occurrence);
    }

    public function mapOccurrenceToOutput(object $occurrence): ?Poster
    {
        $event = $occurrence->event ?? null;

        return $this->createPoster($event, $occurrence);
    }

    public function mapEventToOutput(object $event): object
    {
        $occurrences = [];

        foreach ($event->occurrences as $occurrence) {
            $occurrences[] = new Occurrence(
                $occurrence->entityId,
                $occurrence->start ?? null,
                $occurrence->end ?? null,
                $occurrence->ticketPriceRange ?? null,
            );
        }

        $organizer = new Organizer($event->organizer->name);

        $place = new Place(
            $event->location->name
        );

        $imageUrls = new ImageUrls(
            $event->imageUrls->small ?? null,
            $event->imageUrls->medium ?? null,
            $event->imageUrls->large ?? null,
        );

        return new Event(
            $event->entityId,
            $event->title,
            $organizer,
            $place,
            $imageUrls,
            $occurrences,
        );
    }

    public function toPosterOption(object $entity, string $entityType): PosterOption
    {
        return new PosterOption(
            $entity->name,
            // tag does not have an entityId. Used name instead.
            'tags' == $entityType ? $entity->name : $entity->entityId,
        );
    }
}
