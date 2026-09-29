<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Travel;

readonly class Station
{
    public function __construct(
        // Rejseplanen station id (StopLocation extId).
        public string $id,
        public string $name,
    ) {}
}
