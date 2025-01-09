<?php

namespace App\Feed\OutputModel\Story;

class StoryOutput
{
    public function __construct(
        /** @var Story[] $stories */
        public array $stories,
    ) {}

    public function toArray(): array
    {
        return $this->stories;
    }
}
