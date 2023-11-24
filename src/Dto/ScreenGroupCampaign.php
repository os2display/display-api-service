<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenGroup;

class ScreenGroupCampaign
{
    public Playlist $campaign;
    public ScreenGroup $screenGroup;
}
