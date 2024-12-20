<?php

namespace App\Feed\OutputModel\News;

class News
{
    public function __construct(
        public array $categories,
        public string $title,
        public ?string $content,
        public ?string $summary,
        public ?string $imageUrl,
        public ?string $author,
        public ?string $lastModified,
        public ?string $publisher,
        public ?string $link,
    ) {}
}
