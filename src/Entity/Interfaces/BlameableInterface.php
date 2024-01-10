<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface BlameableInterface
{
    public function getModifiedBy(): string;

    public function setModifiedBy(string $modifiedBy): self;

    public function getCreatedBy(): string;

    public function setCreatedBy(string $createdBy): self;
}
