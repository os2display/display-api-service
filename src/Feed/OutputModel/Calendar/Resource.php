<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Calendar;

class Resource
{
    public function __construct(
        public string $id,
        public string $locationId,
        public string $displayName,
    ) {}
}
