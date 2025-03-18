<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Poster;

readonly class Organizer
{
    public function __construct(
        public string $name,
    ) {}
}
