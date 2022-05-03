<?php

namespace App\Entity\Traits;

use App\Entity\Tenant;
use Doctrine\Common\Collections\Collection;

trait MultiTenantTrait
{
    /**
     * @ORM\ManyToMany(targetEntity=Tenant::class)
     */
    private Collection $tenants;

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(): Collection
    {
        return $this->tenants;
    }

    public function setTenants(array $tenants): self
    {
        $this->tenants->clear();

        foreach ($tenants as $tenant) {
            $this->tenants[] = $tenant;
        }

        return $this;
    }

    public function addTenant(Tenant $tenant): self
    {
        if (!$this->tenants->contains($tenant)) {
            $this->tenants[] = $tenant;
        }

        return $this;
    }

    public function removeTenant(Tenant $tenant): self
    {
        $this->tenants->removeElement($tenant);

        return $this;
    }
}
