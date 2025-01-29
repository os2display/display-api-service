<?php

namespace App\Feed\OutputModel\Poster;

class PosterOutput
{
    public function __construct(
        /** @var Occurrence[] $occurrences */
        public array $occurrences,
    ) {}

    public function toArray(): array
    {
        return $this->occurrences;
    }
}
