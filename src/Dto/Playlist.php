<?php

namespace App\Dto;

class Playlist
{
    use OutputTrait;
    use PublishedTrait;

    public string $schedule = '';
    public string $slides = '';
}
