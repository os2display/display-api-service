<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Calendar;

class Event
{
    public function __construct(
        public string $id,
        public string $title,
        // Unix timestamp.
        public int $startTime,
        // Unix timestamp.
        public int $endTime,
        public string $resourceId,
        public string $resourceTitle,
    ) {}
}
