<?php

namespace App\Entity;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Enum\UserTypeEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User extends AbstractBaseEntity implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable, TenantScopedUserInterface
{
    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    #[Assert\Email]
    private string $email = '';

    /**
     * @ORM\Column(type="string")
     */
    #[Assert\NotBlank]
    private ?string $fullName = null;

    /**
     * @var string The hashed password
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private string $password = '';

    /**
     * @ORM\OneToMany(targetEntity=UserRoleTenant::class, mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $userRoleTenants;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $provider = null;

    /**
     * @ORM\Column(type="string", nullable=true, enumType="App\Enum\UserTypeEnum")
     */
    private ?UserTypeEnum $userType = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $disabled = null;

    private ?Tenant $activeTenant = null;

    // TODO: Add external user identifier.

    public function __construct()
    {
        $this->userRoleTenants = new ArrayCollection();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // If no active Tenant set user has no access.
        if (!isset($this->activeTenant)) {
            return ['ROLE_USER'];
        }

        $roleTenants = $this->getUserRoleTenants();

        $roles = [];

        if (!$roleTenants->isEmpty()) {
            foreach ($roleTenants as $roleTenant) {
                if ($roleTenant->getTenant() === $this->getActiveTenant()) {
                    $roles = $roleTenant->getRoles();
                    break;
                }
            }
        }

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getTenants(): Collection
    {
        $tenants = new ArrayCollection();

        foreach ($this->userRoleTenants as $userRoleTenant) {
            /* @var UserRoleTenant $userRoleTenant */
            $tenants->add($userRoleTenant->getTenant());
        }

        return $tenants;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Tenant
     */
    public function getActiveTenant(): Tenant
    {
        if (null !== $this->activeTenant) {
            return $this->activeTenant;
        }

        return $this->userRoleTenants->first()->getTenant();
    }

    /**
     * @param Tenant $activeTenant
     *
     * @return User
     */
    public function setActiveTenant(Tenant $activeTenant): self
    {
        foreach ($this->userRoleTenants as $userRoleTenant) {
            /** @var UserRoleTenant $userRoleTenant */
            if ($activeTenant === $userRoleTenant->getTenant()) {
                $this->activeTenant = $activeTenant;
                break;
            }
        }

        if ($this->activeTenant !== $activeTenant) {
            throw new \InvalidArgumentException('Active Tenant cannot be set. User does not have access to Tenant: '.$activeTenant->getTenantKey());
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUserRoleTenants(): Collection
    {
        return $this->userRoleTenants;
    }

    public function setRoleTenant(array $roles, Tenant $tenant): self
    {
        $userRoleTenant = $this->getUserRoleTenant($tenant);

        if (null === $userRoleTenant) {
            $userRoleTenant = new UserRoleTenant();
            $userRoleTenant->setTenant($tenant);

            $this->addUserRoleTenant($userRoleTenant);
        }

        $userRoleTenant->setRoles($roles);

        return $this;
    }

    public function setUserRoleTenants(Collection $userRoleTenants): self
    {
        $this->userRoleTenants->clear();

        foreach ($userRoleTenants as $userRoleTenant) {
            $this->addUserRoleTenant($userRoleTenant);
        }

        return $this;
    }

    public function addUserRoleTenant(UserRoleTenant $userRoleTenant): self
    {
        if (!$this->userRoleTenants->contains($userRoleTenant)) {
            $this->userRoleTenants[] = $userRoleTenant;
            $userRoleTenant->setUser($this);
        }

        return $this;
    }

    public function removeUserRoleTenant(UserRoleTenant $userRoleTenant): self
    {
        if ($this->userRoleTenants->removeElement($userRoleTenant)) {
            // set the owning side to null (unless already changed)
            if ($userRoleTenant->getUser() === $this) {
                $userRoleTenant->setUser(null);
            }
        }

        return $this;
    }

    private function getUserRoleTenant(Tenant $tenant): ?UserRoleTenant
    {
        foreach ($this->userRoleTenants as $userRoleTenant) {
            /** @var UserRoleTenant $userRoleTenant */
            if ($userRoleTenant->getTenant() === $tenant) {
                return $userRoleTenant;
            }
        }

        return null;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getUserType(): ?UserTypeEnum
    {
        return $this->userType;
    }

    public function setUserType(?UserTypeEnum $userType): void
    {
        $this->userType = $userType;
    }

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /** {@inheritDoc} */
    final public function jsonSerialize(): array
    {
        return [
            'fullname' => $this->getFullName(),
            'email' => $this->getEmail(),
        ];
    }
}
