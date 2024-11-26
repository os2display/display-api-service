<?php

namespace App\Feed\EventDatabaseApiV2;

class PosterOption {
    public function __construct(
        public readonly string $label,
        public readonly string $value
    )
    {}
}
