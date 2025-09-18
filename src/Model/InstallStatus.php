<?php

declare(strict_types=1);

namespace App\Model;

class InstallStatus
{
    public function __construct(
        public int $installed = 0,
        public int $available = 0,
    ) {}
}
