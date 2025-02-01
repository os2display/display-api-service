<?php

namespace App\Feed\OutputModel\Poster;

readonly class Event
{
    public function __construct(
        public int $entityId,
        public string $title,
        public Organizer $organizer,
        public Place $place,
        public ImageUrls $imageUrls,
        /** @var OccurrenceOption[] $occurrences */
        public array $occurrences,
    ) {}
}
