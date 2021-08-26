<?php

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;

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

    public function setPublishedFrom(DateTimeInterface $publishedFrom): self
    {
        if (!$publishedFrom instanceof DateTimeImmutable) {
            $publishedFrom = DateTimeImmutable::createFromMutable( $publishedFrom );
        }

        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    public function getPublishedTo(): DateTimeImmutable
    {
        return $this->publishedTo;
    }

    public function setPublishedTo(DateTimeInterface $publishedTo): self
    {
        if (!$publishedTo instanceof DateTimeImmutable) {
            $publishedTo = DateTimeImmutable::createFromMutable( $publishedTo );
        }

        $this->publishedTo = $publishedTo;

        return $this;
    }
}
