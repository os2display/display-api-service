<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRoleTenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_role_tenant')]
#[ORM\UniqueConstraint(name: 'user_tenant_unique', columns: ['user_id', 'tenant_id'])]
#[ORM\Entity(repositoryClass: UserRoleTenantRepository::class)]
class UserRoleTenant extends AbstractBaseEntity implements \JsonSerializable
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userRoleTenants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class, inversedBy: 'userRoleTenants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'tenantKey' => $this->getTenant()?->getTenantKey(),
            'title' => $this->getTenant()?->getTitle(),
            'description' => $this->getTenant()?->getDescription(),
            'roles' => $this->getRoles(),
        ];
    }
}
