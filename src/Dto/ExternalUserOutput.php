<?php

namespace App\Dto;

use App\Enum\UserTypeEnum;

class ExternalUserOutput
{
    public ?string $fullName;
    public ?UserTypeEnum $userType = null;
    public array $roles = [];
    public \DateTimeInterface $createdAt;
}
