<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class PlaylistScreenRegion
{
    use IdentifiableTrait;

    #[Groups(['playlist-screen-region:read'])]
    public Playlist $playlist;

    #[Groups(['playlist-screen-region:read'])]
    public int $weight = 0;
}
