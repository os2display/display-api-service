<?php

namespace App\Entity;

use DateTimeImmutable;

trait EntityPublishedTrait
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private DateTimeImmutable $publishedFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private DateTimeImmutable $publishedTo;

    public function getPublishedFrom(): DateTimeImmutable
    {
        return $this->publishedFrom;
    }

    public function setPublishedFrom(DateTimeImmutable $publishedFrom): self
    {
        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    public function getPublishedTo(): DateTimeImmutable
    {
        return $this->publishedTo;
    }

    public function setPublishedTo(DateTimeImmutable $publishedTo): self
    {
        $this->publishedTo = $publishedTo;

        return $this;
    }
}
