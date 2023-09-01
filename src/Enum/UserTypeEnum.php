<?php

namespace App\Enum;

enum UserTypeEnum: string
{
    case OIDC_EXTERNAL = 'oidc-external';
    case OIDC_ACTIVE_DIRECTORY = 'oidc-active-directory';
    case USERNAME_PASSWORD = 'username-password';
}
