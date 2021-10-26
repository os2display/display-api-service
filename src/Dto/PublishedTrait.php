<?php

namespace App\Dto;

trait PublishedTrait
{
    public array $published = [
        'from' => '',
        'to' => '',
    ];

    public function getPublished(): array
    {
        return $this->published;
    }

    public function getPublishedFrom(): \DateTime
    {
        $from = $this->published['from'];

        assert($from instanceof \DateTime);

        return $from;
    }

    public function setPublishedFrom(\DateTime $from): self
    {
        $this->published['from'] = $from;

        return $this;
    }

    public function getPublishedTo(): \DateTime
    {
        $to = $this->published['to'];

        assert($to instanceof \DateTime);

        return $to;
    }

    public function setPublishedTo(\DateTime $to): self
    {
        $this->published['to'] = $to;

        return $this;
    }
}
