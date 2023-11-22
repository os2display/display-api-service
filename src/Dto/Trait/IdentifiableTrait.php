<?php

namespace App\Dto\Trait;

use Symfony\Component\Uid\Ulid;

trait IdentifiableTrait
{
    public Ulid $id;
}
