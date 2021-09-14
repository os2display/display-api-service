<?php

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

trait EntityModificationTrait
{
    /**
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private DateTimeImmutable $created;

    /**
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private DateTimeImmutable $modified;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $modifiedBy = '';

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $createdBy = '';

    /**
     * @return DateTimeInterface
     */
    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @param DateTimeInterface $created
     */
    public function setCreated(DateTimeInterface $created): self
    {
        if (!$created instanceof DateTimeImmutable) {
            $created = DateTimeImmutable::createFromMutable($created);
        }

        $this->created = $created;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    /**
     * @param DateTimeInterface $modified
     */
    public function setModified(DateTimeInterface $modified): self
    {
        if (!$modified instanceof DateTimeImmutable) {
            $modified = DateTimeImmutable::createFromMutable($modified);
        }

        $this->modified = $modified;

        return $this;
    }

    /**
     * @return string
     */
    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    /**
     * @param string $modifiedBy
     */
    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * @param string $createdBy
     */
    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
