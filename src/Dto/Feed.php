<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use App\Dto\Trait\TimestampableTrait;

class Feed
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsChecksumTrait;

    public ?array $configuration = [];
    public Slide $slide;
    public FeedSource $feedSource;
    public string $feedUrl;
}
