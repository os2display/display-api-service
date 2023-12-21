<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\RelationsModifiedTrait;
use App\Entity\Tenant\Playlist;

class PlaylistScreenRegion
{
    use RelationsModifiedTrait;

    public Playlist $playlist;
    public int $weight = 0;
}
