<?php

namespace App\Dto;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;

class ScreenCampaign
{
    public Playlist $campaign;
    public Screen $screen;
}
