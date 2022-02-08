<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractBaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     * @ApiProperty(identifier=true)
     */
    private Ulid $id;

    /**
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private DateTimeImmutable $modifiedAt;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $createdBy = '';

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $modifiedBy = '';

    /**
     * Get the Ulid.
     */
    public function getId(): ?Ulid
    {
        return $this->id;
    }

    /**
     * Set the Ulid.
     */
    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): self
    {
        $this->createdAt = $this->id->getDateTime();

        return $this;
    }

    public function getModifiedAt(): DateTimeInterface
    {
        return $this->modifiedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setModifiedAtValue(): self
    {
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
