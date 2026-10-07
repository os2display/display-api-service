<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Template;
use App\Enum\ResourceTypeEnum;

class TemplateData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly array $adminForm,
        public readonly object $options,
        public readonly ?Template $templateEntity,
        public readonly bool $installed,
        public readonly ResourceTypeEnum $type,
    ) {}
}
