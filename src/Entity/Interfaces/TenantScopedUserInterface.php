<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity\Tenant;
use Doctrine\Common\Collections\Collection;

interface TenantScopedUserInterface
{
    public function getActiveTenant(): Tenant;

    public function setActiveTenant(Tenant $activeTenant): self;

    public function getTenants(): Collection;

    public function getUserRoleTenants(): Collection;
}
