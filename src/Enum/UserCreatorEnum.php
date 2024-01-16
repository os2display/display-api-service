<?php

declare(strict_types=1);

namespace App\Enum;

enum UserCreatorEnum: string
{
    case CLI = 'CLI';
    case OIDC = 'OIDC';
}
