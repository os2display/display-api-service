<?php

namespace App\Feed\OutputModel\Poster;

readonly class OccurrenceOption
{
    public function __construct(
        public int $entityId,
        public string $start,
        public string $end,
    ) {}
}
