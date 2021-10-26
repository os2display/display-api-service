<?php

namespace App\Dto;

interface PublishedInterface
{
    public function getPublished(): array;

    public function getPublishedFrom(): \DateTime;

    public function setPublishedFrom(\DateTime $from): self;

    public function getPublishedTo(): \DateTime;

    public function setPublishedTo(\DateTime $to): self;
}