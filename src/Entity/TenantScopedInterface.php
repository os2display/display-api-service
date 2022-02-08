<?php

namespace App\Entity;

interface TenantScopedInterface
{
    public function getTenant(): Tenant;

    public function setTenant(Tenant $tenant): self;
}
