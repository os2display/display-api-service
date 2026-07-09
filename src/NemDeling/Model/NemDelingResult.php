<?php

declare(strict_types=1);

namespace App\NemDeling\Model;

final class NemDelingResult
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
    ) {}
}
