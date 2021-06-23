<?php

namespace App\Api\Model;

/**
 * Class Shared.
 *
 * Properties that are shared between the different data models.
 */
class Shared
{
    private string $title = '';
    private string $description = '';
    private array $tags = [];
    private int $modified = 0;
    private int $created = 0;
    private string $modifiedBy = '';
    private string $createdBy = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getModified(): int
    {
        return $this->modified;
    }

    /**
     * @return $this
     */
    public function setModified(int $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @return $this
     */
    public function setCreated(int $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    /**
     * @return $this
     */
    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * @return $this
     */
    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
