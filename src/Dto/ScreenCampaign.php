<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;

class ScreenCampaign
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    public Playlist $campaign;
    public Screen $screen;
}
