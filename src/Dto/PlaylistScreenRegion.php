<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Tenant\Playlist;

class PlaylistScreenRegion
{
    public Playlist $playlist;
    public int $weight = 0;
}
