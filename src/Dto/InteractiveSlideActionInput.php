<?php

declare(strict_types=1);

namespace App\Dto;

class InteractiveSlideActionInput
{
    public ?string $implementationClass = null;
    public ?string $action = null;
    public ?array $data = null;
}
