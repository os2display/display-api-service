<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Poster;

class Place
{
    public function __construct(
        public ?string $name,
        public ?string $streetAddress = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $image = null,
        public ?string $telephone = null,
    ) {}
}
