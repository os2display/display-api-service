<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface TimestampableInterface
{
    public function getCreatedAt(): \DateTimeInterface;

    public function setCreatedAt(): self;

    public function getModifiedAt(): ?\DateTimeInterface;

    public function setModifiedAt(): self;
}
