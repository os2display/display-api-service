<?php

declare(strict_types=1);

namespace App\Model;

class CalendarResource
{
    public function __construct(
        public string $id,
        public string $locationId,
        public string $displayName,
    ) {}
}
