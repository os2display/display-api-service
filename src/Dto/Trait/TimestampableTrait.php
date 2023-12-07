<?php

declare(strict_types=1);

namespace App\Dto\Trait;

trait TimestampableTrait
{
    public \DateTimeImmutable $created;
    public \DateTimeImmutable $modified;
}
