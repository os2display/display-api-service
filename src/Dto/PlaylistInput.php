<?php

namespace App\Dto;

class PlaylistInput
{
    public string $title = '';
    public string $description = '';
    public array $schedules = [];
    public array $tenants = [];
    public bool $isCampaign;
    public array $published = [
        'from' => '0',
        'to' => '0',
    ];
}
