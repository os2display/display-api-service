<?php

namespace App\Feed\OutputModel\Poster;

class Place
{
    public function __construct(
        public ?string $name,
        public ?string $streetAddress,
        public ?string $postalCode,
        public ?string $city,
        public ?string $image,
        public ?string $telephone,
    ) {}
}
