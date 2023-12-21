<?php

declare(strict_types=1);

namespace App\Dto\Trait;

trait RelationsModifiedTrait
{
    private object $relationsModified;

    public function getRelationsModified(): object
    {
        return $this->relationsModified;
    }

    public function setRelationsModified(array $relationsModified): void
    {
        $this->relationsModified = (object) $relationsModified;
    }
}
