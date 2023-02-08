<?php

namespace App\Entity\Tenant;

use App\Entity\AbstractBaseEntity;
use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 *
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractTenantScopedEntity extends AbstractBaseEntity implements TenantScopedEntityInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=Tenant::class)
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private Tenant $tenant;

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
