<?php

declare(strict_types=1);

namespace App\Dto\Trait;

trait TimestampableTrait
{
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
}
