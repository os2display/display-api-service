<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Entity\Tenant\Playlist;
use Symfony\Component\Serializer\Annotation\Groups;

class PlaylistSlide
{
    use IdentifiableTrait;

    #[Groups(['playlist-slide:read'])]
    public Slide $slide;

    #[Groups(['playlist-slide:read'])]
    public Playlist $playlist;

    #[Groups(['playlist-slide:read'])]
    public int $weight = 0;
}
