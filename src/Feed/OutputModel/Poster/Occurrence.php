<?php

namespace App\Feed\OutputModel\Poster;

readonly class Occurrence
{
    public function __construct(
        public ?int $eventId,
        public ?int $occurrenceId,
        public ?string $ticketPurchaseUrl,
        public ?string $description,
        public ?string $excerpt,
        public ?string $name,
        public ?string $url,
        public ?string $baseUrl,
        public ?string $image,
        public ?string $startDate,
        public ?string $endDate,
        public ?string $ticketPriceRange,
        public ?string $eventStatusText,
        public ?Organizer $organizer,
        public ?Place $place,
    ) {}
}
