<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Poster;

class PosterOutput
{
    public function __construct(
        /** @var Poster[] $posters */
        public array $posters,
    ) {}

    public function toArray(): array
    {
        return $this->posters;
    }
}
