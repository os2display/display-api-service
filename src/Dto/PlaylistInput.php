<?php

namespace App\Dto;

class PlaylistInput
{
    public string $title = '';
    public string $description = '';
    public string $schedule = '';
    public string $modifiedBy = '';
    public string $createdBy = '';
    public array $published = [
        'from' => '0',
        'to' => '0',
    ];
}
