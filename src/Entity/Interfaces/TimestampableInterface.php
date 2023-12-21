<?php

namespace App\Entity\Interfaces;

interface TimestampableInterface
{
    public function getCreatedAt(): \DateTimeInterface;

    public function setCreatedAt(): self;

    public function getModifiedAt(): ?\DateTimeInterface;

    public function setModifiedAt(): self;
}
