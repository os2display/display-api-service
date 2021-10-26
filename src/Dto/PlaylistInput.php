<?php

namespace App\Dto;

class PlaylistInput implements InputInterface, PublishedInterface
{
    use InputTrait;
    use PublishedTrait;

    public string $schedule = '';
}
