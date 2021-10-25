<?php

namespace App\Dto;

class PlaylistInput
{
    use InputTrait;
    use PublishedTrait;

    public string $schedule = '';
}
