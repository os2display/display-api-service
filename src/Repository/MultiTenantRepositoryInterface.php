<?php

namespace App\Repository;

use App\Entity\Tenant;

interface MultiTenantRepositoryInterface
{
    public function addTenantToAll(Tenant $tenant): void;
}
