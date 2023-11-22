<?php

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;

class ScreenCampaign
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public Playlist $campaign;
    public Screen $screen;
}
