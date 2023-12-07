<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Enum\UserTypeEnum;

class UserOutput
{
    use IdentifiableTrait;

    public ?string $fullName;
    public ?UserTypeEnum $userType = null;
    public array $roles = [];
    public \DateTimeInterface $createdAt;
}
