<?php

namespace App\Entity\Interfaces;

use App\Entity\Tenant;
use Doctrine\Common\Collections\Collection;

interface MultiTenantInterface
{
    public function getTenants(): Collection;

    public function setTenants(Collection $tenants): self;

    public function addTenant(Tenant $tenant): self;

    public function removeTenant(Tenant $tenant): self;
}
