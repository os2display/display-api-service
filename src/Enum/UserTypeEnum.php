<?php

namespace App\Enum;

enum UserTypeEnum: string
{
    case OIDC_EXTERNAL = 'oidc-external';
    case OIDC_INTERNAL = 'oidc-internal';
    case USERNAME_PASSWORD = 'username-password';
}
