<?php

namespace App\Dto;

use Symfony\Component\Uid\Ulid;

class Screen
{
    public Ulid $id;
    public string $title = '';
    public string $description = '';
    public string $size = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $campaigns = '';
    public string $layout = '';
    public string $orientation = '';
    public string $resolution = '';
    public string $location = '';
    public array $regions = [];
    public string $inScreenGroups = '/v1/screens/{id}/groups';

    public ?string $screenUser;
    public ?bool $enableColorSchemeChange = null;
}
