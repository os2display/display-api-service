<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\EntityListeners([\App\EventListener\TenantDoctrineEventListener::class])]
class Tenant extends AbstractBaseEntity implements \JsonSerializable
{
    use EntityTitleDescriptionTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 25, unique: true)]
    private string $tenantKey = '';

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\UserRoleTenant>|\App\Entity\UserRoleTenant[]
     */
    #[ORM\OneToMany(targetEntity: UserRoleTenant::class, mappedBy: 'tenant', orphanRemoval: true)]
    private Collection $userRoleTenants;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $fallbackImageUrl = null;

    public function __construct()
    {
        $this->userRoleTenants = new ArrayCollection();
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
