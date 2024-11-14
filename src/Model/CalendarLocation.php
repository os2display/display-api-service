<?php

declare(strict_types=1);

namespace App\Model;

class CalendarLocation
{
    public function __construct(
        public string $id,
        public string $displayName,
    ) {}
}
