<?php

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\TimestampableTrait;

class Feed
{
    use BlameableTrait;
    use TimestampableTrait;

    public ?array $configuration = [];
    public ?string $slide = null;
    public ?string $feedSource = null;
    public ?string $feedUrl = null;
}
