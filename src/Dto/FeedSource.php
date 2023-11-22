<?php

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;

class FeedSource
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public string $title = '';
    public string $description = '';
    public string $outputType = '';
    public string $feedType = '';
    public array $secrets = [];
    public array $feeds = [];
    public array $admin = [];
    public string $supportedFeedOutputType = '';
}
