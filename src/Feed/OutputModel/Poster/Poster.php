<?php

namespace App\Feed\OutputModel\Poster;

class Poster
{

    public function __construct(
        public string $eventId,
        public string $occurrenceId,
        public string $ticketPurchaseUrl,
        public string $excerpt,
        public string $name,
        public string $url,
        public string $baseUrl,
        public string $image,
        public string $startDate,
        public string $endDate,
        public string $ticketPriceRange,
        public string $eventStatusText,
        public Place $place,
    )
    {
    }
}
