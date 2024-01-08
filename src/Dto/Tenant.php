<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;

class Tenant
{
    use IdentifiableTrait;
    use TimestampableTrait;

    public string $title = '';
    public string $description = '';
    public string $tenantKey = '';
    public ?string $fallbackImageUrl = null;
}
