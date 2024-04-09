<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;

class ScreenLayoutRegions
{
    use IdentifiableTrait;

    public string $title = '';
    public array $gridArea = [];
    public string $type = '';
}
