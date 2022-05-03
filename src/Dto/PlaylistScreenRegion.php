<?php

namespace App\Dto;

use App\Entity\Tenant\Playlist;

class PlaylistScreenRegion
{
    public Playlist $playlist;
    public int $weight = 0;
}
