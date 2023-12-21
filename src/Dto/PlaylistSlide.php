<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\RelationsModifiedTrait;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Slide;

class PlaylistSlide
{
    use RelationsModifiedTrait;

    public Slide $slide;
    public Playlist $playlist;
    public int $weight = 0;
}
