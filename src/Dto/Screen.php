<?php

namespace App\Dto;

class Screen
{
    public string $title = '';
    public string $description = '';
    public string $size = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $layout;
    public string $location = '';
    public array $regions = [
        '/v1/screens/{id}/regions/{regionId}/playlists'
    ];
    public string $inScreenGroups = '/v1/screens/{id}/groups/{groupId}';
    public array $dimensions = [
        'width' => 0,
        'height' => 0,
    ];
}
