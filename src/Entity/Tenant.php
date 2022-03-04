<?php

namespace App\Entity;

use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TenantRepository::class)
 */
class Tenant extends AbstractBaseEntity implements \JsonSerializable
{
    use EntityTitleDescriptionTrait;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private string $tenantKey;

    /**
     * @ORM\OneToMany(targetEntity=UserRoleTenant::class, mappedBy="tenant", orphanRemoval=true)
     */
    private Collection $userRoleTenants;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->userRoleTenants = new ArrayCollection();
    }

    /**
     * @return Collection|UserRoleTenant[]
     */
    public function getUserRoleTenants(): Collection
    {
        return $this->userRoleTenants;
    }

    public function addUserRoleTenant(UserRoleTenant $userRoleTenant): self
    {
        if (!$this->userRoleTenants->contains($userRoleTenant)) {
            $this->userRoleTenants[] = $userRoleTenant;
            $userRoleTenant->setTenant($this);
        }

        return $this;
    }

    public function removeUserRoleTenant(UserRoleTenant $userRoleTenant): self
    {
        if ($this->userRoleTenants->removeElement($userRoleTenant)) {
            // set the owning side to null (unless already changed)
            if ($userRoleTenant->getTenant() === $this) {
                $userRoleTenant->setTenant(null);
            }
        }

        return $this;
    }

    public function getTenantKey(): ?string
    {
        return $this->tenantKey;
    }

    public function setTenantKey(string $tenantKey): self
    {
        $this->tenantKey = $tenantKey;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'tenantKey' => $this->getTenantKey(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
        ];
    }
}
