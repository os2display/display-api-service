<?php

declare(strict_types=1);

namespace App\Dto;

class FeedSourceInput
{
    public string $title = '';
    public string $description = '';
    public string $outputType = '';
    public string $feedType = '';
    public array $secrets = [];
    public array $feeds = [];
    public string $supportedFeedOutputType = '';
}
