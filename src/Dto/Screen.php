<?php

namespace App\Dto;

class Screen implements OutputInterface
{
    use OutputTrait;

    public string $size = '';
    public string $layout = '';
    public string $location = '';
    public array $regions = [];
    public string $inScreenGroups = '/v1/screens/{id}/groups';
    public array $dimensions = [
        'width' => 0,
        'height' => 0,
    ];
}
