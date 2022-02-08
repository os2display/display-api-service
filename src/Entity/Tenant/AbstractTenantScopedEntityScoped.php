<?php

namespace App\Entity\Tenant;

use App\Entity\Tenant;
use App\Entity\TenantScopedInterface;

abstract class AbstractTenantScopedEntityScoped implements TenantScopedInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=Tenant::class)
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