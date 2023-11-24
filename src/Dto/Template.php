<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;

class Template
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public string $title = '';
    public string $description = '';
    public array $resources = [];
}
