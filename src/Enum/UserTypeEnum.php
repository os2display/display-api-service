<?php

namespace App\Enum;

enum UserTypeEnum: string
{
    case OIDC_EXTERNAL = 'OIDC_EXTERNAL';
    case OIDC_INTERNAL = 'OIDC_INTERNAL';
    case USERNAME_PASSWORD = 'USERNAME_PASSWORD';
}
