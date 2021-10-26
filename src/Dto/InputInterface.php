<?php

namespace App\Dto;

interface InputInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     */
    public function setTitle(string $title): void;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param string $description
     */
    public function setDescription(string $description): void;

    /**
     * @return string
     */
    public function getModifiedBy(): string;

    /**
     * @param string $modifiedBy
     */
    public function setModifiedBy(string $modifiedBy): void;

    /**
     * @return string
     */
    public function getCreatedBy(): string;

    /**
     * @param string $createdBy
     */
    public function setCreatedBy(string $createdBy): void;
}
