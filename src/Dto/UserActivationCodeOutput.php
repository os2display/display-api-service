<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;

class UserActivationCodeOutput
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public ?string $code;
    public ?\DateTimeImmutable $codeExpire;
    public ?string $username;
    public ?array $roles = [];
}
