<?php

declare(strict_types=1);

namespace App\Dto\Trait;

trait BlameableTrait
{
    public string $modifiedBy = '';
    public string $createdBy = '';
}
