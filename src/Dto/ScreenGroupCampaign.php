<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\RelationsModifiedTrait;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenGroup;

class ScreenGroupCampaign
{
    use RelationsModifiedTrait;

    public Playlist $campaign;
    public ScreenGroup $screenGroup;
}
