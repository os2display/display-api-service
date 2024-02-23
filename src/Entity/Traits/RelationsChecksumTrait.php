<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait RelationsChecksumTrait
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: false)]
    private bool $changed = true;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: false, options: ['default' => '{}'])]
    private array $relationsChecksum = [];

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function setChanged(bool $changed): self
    {
        $this->changed = $changed;

        return $this;
    }

    public function getRelationsChecksum(): array
    {
        return array_filter($this->relationsChecksum);
    }

    public function setRelationsChecksum(array $relationsChecksum): void
    {
        $this->relationsChecksum = $relationsChecksum;
    }
}
