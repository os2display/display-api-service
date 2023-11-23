<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;

class Screen
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public string $title = '';
    public string $description = '';
    public string $size = '';

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
