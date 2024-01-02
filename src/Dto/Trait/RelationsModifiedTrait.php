<?php

declare(strict_types=1);

namespace App\Dto\Trait;

trait RelationsModifiedTrait
{
    private array $relationsModified;

    public function getRelationsModified(): array
    {
        return $this->relationsModified;
    }

    public function setRelationsModified(array $relationsModified): void
    {
        $this->relationsModified = $relationsModified;
    }
}
