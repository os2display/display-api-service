<?php

declare(strict_types=1);

namespace App\Model;

class CalendarEvent
{
    public function __construct(
        public string $id,
        public string $title,
        public int $startTimeTimestamp,
        public int $endTimeTimestamp,
        public string $resourceId,
        public string $resourceDisplayName,
    ) {}
}
