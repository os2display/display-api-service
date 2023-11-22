<?php

namespace App\Dto\Trait;

trait TimestampableTrait
{
    public \DateTimeImmutable $created;
    public \DateTimeImmutable $modified;
}
