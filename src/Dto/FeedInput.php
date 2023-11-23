<?php

declare(strict_types=1);

namespace App\Dto;

class FeedInput
{
    public ?array $configuration = [];
    public ?string $slide = '';
    public ?string $feedSource = '';
}
