<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\ScreenLayout;

class ScreenLayoutData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $type,
        public readonly int $gridRows,
        public readonly int $gridColumns,
        public readonly ?ScreenLayout $screenLayoutEntity,
        public readonly bool $installed,
        public readonly array $regions,
    ) {}
}
