<?php

declare(strict_types=1);

namespace App\Dto;

class UserActivationCodeInput
{
    public string $displayName = '';
    public array $roles = [];
}
