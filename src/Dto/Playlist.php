<?php

namespace App\Dto;

class Playlist implements OutputInterface
{
    use OutputTrait;
    use PublishedTrait;

    public string $schedule = '';
    public string $slides = '';
}
