<?php

namespace App\Dto;

class Playlist
{
    public string $title = '';
    public string $description = '';
    public array $schedules = [];
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public string $slides = '';
    public array $published = [
        'from' => '',
        'to' => '',
    ];
}
