<?php

declare(strict_types=1);

namespace App\Dto\Trait;

use Symfony\Component\Uid\Ulid;

trait IdentifiableTrait
{
    public Ulid $id;
}
