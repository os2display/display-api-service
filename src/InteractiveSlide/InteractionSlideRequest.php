<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

readonly class InteractionSlideRequest
{
    public function __construct(
        public string $implementationClass,
        public string $action,
        public array $data,
    ) {}
}
