<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Interfaces\BlameableInterface;
use App\Entity\Interfaces\TimestampableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Serializer\Annotation  as Serializer;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractBaseEntity implements BlameableInterface, TimestampableInterface
{
    #[ApiProperty(identifier: true)]
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Version]
    #[Serializer\Ignore]
    protected int $version = 1;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $modifiedAt;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: false, options: ['default' => ''])]
    private string $createdBy = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: false, options: ['default' => ''])]
    private string $modifiedBy = '';

    public function __construct()
    {
        $this->modifiedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

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

        $this->createdAt = $this->id->getDateTime();

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt = null): self
    {
        if (null === $createdAt) {
            $this->createdAt = isset($this->id) ? $this->id->getDateTime() : new \DateTimeImmutable();
        } else {
            $this->createdAt = \DateTimeImmutable::createFromInterface($createdAt);
        }

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt ?? null;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt = null): self
    {
        $this->modifiedAt = $modifiedAt ? \DateTimeImmutable::createFromInterface($modifiedAt) : new \DateTimeImmutable();

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
