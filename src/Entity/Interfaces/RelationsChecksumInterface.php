<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface RelationsChecksumInterface
{
    public function isChanged(): bool;

    public function setChanged(bool $changed): self;

    public function getRelationsChecksum(): array;

    public function setRelationsChecksum(array $relationsChecksum): void;
}
