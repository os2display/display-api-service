<?php

namespace App\Dto;

interface OutputInterface extends InputInterface
{
    /**
     * @return \DateTimeInterface
     */
    public function getCreated(): \DateTimeInterface;

    /**
     * @param \DateTimeInterface $created
     */
    public function setCreated(\DateTimeInterface $created): void;

    /**
     * @return \DateTimeInterface
     */
    public function getModified(): \DateTimeInterface;

    /**
     * @param \DateTimeInterface $modified
     */
    public function setModified(\DateTimeInterface $modified): void;
}