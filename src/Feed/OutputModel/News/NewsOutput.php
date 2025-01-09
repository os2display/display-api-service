<?php

namespace App\Feed\OutputModel\News;

use App\Feed\OutputModel\OutputInterface;

class NewsOutput implements OutputInterface
{
    public function __construct(
        /** @var News[] $news */
        public array $news,
    ) {}

    public function toArray(): array
    {
        return $this->news;
    }
}
