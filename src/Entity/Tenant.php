<?php

namespace App\Entity;

use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TenantRepository::class)
 *
 * @ORM\EntityListeners({"App\EventListener\TenantDoctrineEventListener"})
 */
class Tenant extends AbstractBaseEntity implements \JsonSerializable
{
    use EntityTitleDescriptionTrait;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private string $tenantKey = '';

    /**
     * @ORM\OneToMany(targetEntity=UserRoleTenant::class, mappedBy="tenant", orphanRemoval=true)
     */
    private Collection $userRoleTenants;

    /**
     * @ORM\Column(type="string", nullable="true")
     */
    private ?string $fallbackImageUrl = null;

    /**
     * @ORM\OneToMany(targetEntity=ExternalUserActivationCode::class, mappedBy="tenant", orphanRemoval=true)
     */
    private Collection $externalUserActivationCodes;

    public function __construct()
    {
        $this->userRoleTenants = new ArrayCollection();
        $this->externalUserActivationCodes = new ArrayCollection();
    }

    /**
     * @return Collection
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

    /**
     * @return Collection
     */
    public function getExternalUserActivationCodes(): Collection
    {
        return $this->externalUserActivationCodes;
    }

    // TODO: Remove?
    public function addExternalUserActivationCode(ExternalUserActivationCode $externalUserActivationCode): self
    {
        if (!$this->externalUserActivationCodes->contains($externalUserActivationCode)) {
            $this->externalUserActivationCodes[] = $externalUserActivationCode;
            $externalUserActivationCode->setTenant($this);
        }

        return $this;
    }

    // TODO: Remove?
    public function removeExternalUserActivationCode(ExternalUserActivationCode $externalUserActivationCode): self
    {
        if ($this->externalUserActivationCodes->removeElement($externalUserActivationCode)) {
            // set the owning side to null (unless already changed)
            if ($externalUserActivationCode->getTenant() === $this) {
                $externalUserActivationCode->setTenant(null);
            }
        }

        return $this;
    }

    public function getTenantKey(): string
    {
        return $this->tenantKey;
    }

    public function setTenantKey(string $tenantKey): self
    {
        $this->tenantKey = $tenantKey;

        return $this;
    }

    public function getTitle(): string
    {
        return empty($this->title) ? $this->tenantKey : $this->title;
    }

    public function getDescription(): string
    {
        return empty($this->description) ? $this->tenantKey : $this->description;
    }

    public function getFallbackImageUrl(): ?string
    {
        return $this->fallbackImageUrl;
    }

    public function setFallbackImageUrl(?string $fallbackImageUrl): self
    {
        $this->fallbackImageUrl = $fallbackImageUrl;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'tenantKey' => $this->getTenantKey(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'fallbackImageUrl' => $this->getFallbackImageUrl(),
        ];
    }
}
