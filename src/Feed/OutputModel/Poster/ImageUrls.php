<?php

namespace App\Feed\OutputModel\Poster;

readonly class ImageUrls
{
    public function __construct(
        public ?string $small,
        public ?string $medium,
        public ?string $large,
    ) {}
}
