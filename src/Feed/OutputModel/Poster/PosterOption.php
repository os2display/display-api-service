<?php

namespace App\Feed\OutputModel\Poster;

class PosterOption {
    public function __construct(
        public readonly string $label,
        public readonly string $value
    )
    {}
}
