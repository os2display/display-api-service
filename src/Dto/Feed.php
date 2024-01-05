<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;

class Feed
{
    use BlameableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    public ?array $configuration = [];
    public Slide $slide;
    public FeedSource $feedSource;
}
