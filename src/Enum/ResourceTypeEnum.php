<?php

declare(strict_types=1);

namespace App\Enum;

enum ResourceTypeEnum: string
{
    case CORE = 'Core';
    case CUSTOM = 'Custom';
}
