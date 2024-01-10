<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity\Tenant;
use Doctrine\Common\Collections\Collection;

interface MultiTenantInterface
{
    public function getTenants(): Collection;

    public function setTenants(array $tenants): self;

    public function addTenant(Tenant $tenant): self;

    public function removeTenant(Tenant $tenant): self;
}
