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

    /**
     * @return DateTimeImmutable
     */
    public function getPublishedFrom(): DateTimeImmutable
    {
        return $this->publishedFrom;
    }

    /**
     * @param DateTimeImmutable $publishedFrom
     */
    public function setPublishedFrom(DateTimeImmutable $publishedFrom): self
    {
        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getPublishedTo(): DateTimeImmutable
    {
        return $this->publishedTo;
    }

    /**
     * @param DateTimeImmutable $publishedTo
     */
    public function setPublishedTo(DateTimeImmutable $publishedTo): self
    {
        $this->publishedTo = $publishedTo;

        return $this;
    }


}