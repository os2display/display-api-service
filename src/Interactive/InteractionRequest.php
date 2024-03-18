<?php

declare(strict_types=1);

namespace App\Interactive;

readonly class InteractionRequest
{
    public function __construct(
        public string $implementationClass,
        public string $action,
        public array $data
    ) {}
}
