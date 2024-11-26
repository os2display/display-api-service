<?php

namespace App\Feed\EventDatabaseApiV2;

class PosterLocation
{
    public function __construct(
        public string $name,
        public string $streetAddress,
        public string $addressLocality,
        public string $postalCode,
        public string $image,
        public string $telephone,
    ) {}
}
