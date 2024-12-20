<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Calendar;

class Location
{
    public function __construct(
        public string $id,
        public string $displayName,
    ) {}
}
