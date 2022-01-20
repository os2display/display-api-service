<?php

namespace App\Dto;

class FeedSourceInput
{
    public string $title = '';
    public string $description = '';
    public string $outputType = '';
    public string $feedType = '';
    public array $secrets = [];
    public array $configuration = [];
    public array $feeds = [];
}
