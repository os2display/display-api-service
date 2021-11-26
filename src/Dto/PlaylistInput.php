<?php

namespace App\Dto;

class PlaylistInput
{
    public string $title = '';
    public string $description = '';
    public array $schedules = [
        [
            'rrule' => '',
            'duration' => 0,
        ]
    ];
    public string $modifiedBy = '';
    public string $createdBy = '';
    public array $published = [
        'from' => '0',
        'to' => '0',
    ];
}
