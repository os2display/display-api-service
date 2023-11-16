<?php

namespace App\Dto\Trait;

trait TimestampableTrait
{
    public \DateTimeImmutable $createdAt;
    public \DateTimeImmutable $modifiedAt;
}
