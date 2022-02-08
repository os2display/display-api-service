<?php

namespace App\Entity\Tenant;

use App\Entity\Tenant;
use App\Entity\TenantScopedInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractTenantScopedEntityScoped implements TenantScopedInterface
{
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
     * @ORM\ManyToOne(targetEntity=Tenant::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Tenant $tenant;

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getModifiedAt(): DateTimeInterface
    {
        return $this->modifiedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setModifiedAt(): self
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

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function setTenant(Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }
}
