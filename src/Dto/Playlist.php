<?php

namespace App\Dto;

class Playlist implements OutputInterface, PublishedInterface
{
    use OutputTrait;
    use PublishedTrait;

    public string $schedule = '';
    public string $slides = '';
}
