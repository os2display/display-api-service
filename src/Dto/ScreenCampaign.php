<?php

namespace App\Dto;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;
use Symfony\Component\Uid\Ulid;

class ScreenCampaign
{
    public Ulid $id;
    public Playlist $campaign;
    public Screen $screen;
}
