<?php

namespace App\Feed\OutputModel\Story;

class Story
{
    public function __construct(
        public string $text,
        public ?string $textMarkup,
        public ?string $mediaUrl,
        public ?string $videoUrl,
        public ?string $username,
        public ?string $createdTime,
    ) {}
}
