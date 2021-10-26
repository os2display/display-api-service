<?php

namespace App\Entity;

interface EntitySharedInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     */
    public function setTitle(string $title): self;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param string $description
     */
    public function setDescription(string $description): self;

    /**
     * @return string
     */
    public function getModifiedBy(): string;

    /**
     * @param string $modifiedBy
     */
    public function setModifiedBy(string $modifiedBy): self;

    /**
     * @return string
     */
    public function getCreatedBy(): string;

    /**
     * @param string $createdBy
     */
    public function setCreatedBy(string $createdBy): self;
}
