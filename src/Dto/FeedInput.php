<?php

namespace App\Dto;

class FeedInput
{
    public string $title = '';
    public string $description = '';
    public ?array $configuration = [];
    public string $slide = '';
    public string $feedSource = '';
}
