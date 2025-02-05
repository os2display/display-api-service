<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Poster;

readonly class Occurrence
{
    public function __construct(
        public int $entityId,
        public ?string $start,
        public ?string $end,
        public ?string $ticketPriceRange,
    ) {}
}
