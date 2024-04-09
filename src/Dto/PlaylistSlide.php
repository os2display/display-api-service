<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class PlaylistSlide
{
    use IdentifiableTrait;
    use RelationsChecksumTrait;

    #[Groups(['playlist-slide:read', 'slides/playlists:read'])]
    public Slide $slide;

    #[Groups(['playlist-slide:read', 'slides/playlists:read'])]
    public Playlist $playlist;

    #[Groups(['playlist-slide:read', 'slides/playlists:read'])]
    public int $weight = 0;
}
