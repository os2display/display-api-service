<?php

declare(strict_types=1);

namespace App\NemDeling\Model;

final class NemDelingSlide
{
    public function __construct(
        public readonly string $templateId,
        public readonly array $content,
        public readonly string $type = 'event',
    ) {}
}
