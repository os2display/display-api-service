<?php

namespace App\Dto;

use App\Entity\Tenant\Slide;

class PlaylistSlide
{
    public Slide $slide;
    public int $weight = 0;
}
