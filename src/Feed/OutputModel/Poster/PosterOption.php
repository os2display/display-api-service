<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Poster;

readonly class PosterOption
{
    public function __construct(
        public string $label,
        public string|int $value,
    ) {}
}
