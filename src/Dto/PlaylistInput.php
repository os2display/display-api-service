<?php

namespace App\Dto;

class PlaylistInput
{
    public string $title = '';
    public string $description = '';
    public string $modifiedBy = '';
    public string $createdBy = '';
    public array $published = [
        'from' => '2021-09-21T17:00:01Z',
        'to' => '2021-07-22T17:00:01Z',
    ];
}
