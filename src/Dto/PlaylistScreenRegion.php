<?php

namespace App\Dto;

use App\Entity\Playlist;

class PlaylistScreenRegion
{
    public Playlist $playlist;
    public int $weight = 0;
}
