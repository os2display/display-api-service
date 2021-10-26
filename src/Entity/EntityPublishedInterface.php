<?php

namespace App\Entity;

interface EntityPublishedInterface
{
    public function getPublishedFrom(): ?\DateTime;

    public function setPublishedFrom(?\DateTime $publishedFrom): self;

    public function getPublishedTo(): ?\DateTime;

    public function setPublishedTo(?\DateTime $publishedTo): self;
}