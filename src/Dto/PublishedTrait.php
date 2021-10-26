<?php

namespace App\Dto;

trait PublishedTrait
{
    public array $published = [
        'from' => null,
        'to' => null,
    ];

    public function getPublished(): array
    {
        return $this->published;
    }

    public function getPublishedFrom(): ?\DateTime
    {
        return $this->published['from'];
    }

    public function setPublishedFrom(?\DateTime $from): self
    {
        $this->published['from'] = $from;

        return $this;
    }

    public function getPublishedTo(): ?\DateTime
    {
        return $this->published['to'];
    }

    public function setPublishedTo(?\DateTime $to): self
    {
        $this->published['to'] = $to;

        return $this;
    }
}
