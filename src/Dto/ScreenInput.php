<?php

namespace App\Dto;

class ScreenInput
{
    public string $title = '';
    public string $description = '';
    public string $size = '';
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $layout = '';
    public string $location = '';
    public array $regions = [
        '/v1/screens/{id}/regions/{regionId}/playlists',
    ];
    public array $dimensions = [
        'width' => 0,
        'height' => 0,
    ];
}
