<?php

namespace App\Dto;

trait OutputTrait
{
    use InputTrait;

    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;

    /**
     * @return \DateTimeInterface
     */
    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @param \DateTimeInterface $created
     */
    public function setCreated(\DateTimeInterface $created): void
    {
        $this->created = $created;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    /**
     * @param \DateTimeInterface $modified
     */
    public function setModified(\DateTimeInterface $modified): void
    {
        $this->modified = $modified;
    }
}