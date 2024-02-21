<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class PlaylistScreenRegion
{
    use IdentifiableTrait;
    use RelationsChecksumTrait;

    #[Groups(['playlist-screen-region:read'])]
    public Playlist $playlist;

    #[Groups(['playlist-screen-region:read'])]
    public int $weight = 0;
}
