<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\Interfaces\UserInterface;
use App\Entity\Tenant\AbstractTenantScopedEntity;
use App\Entity\Tenant\Screen;
use App\Repository\ScreenUserRepository;
use App\Utils\Roles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: ScreenUserRepository::class)]
class ScreenUser extends AbstractTenantScopedEntity implements UserInterface, TenantScopedUserInterface
{
    final public const string ROLE_SCREEN = Roles::ROLE_SCREEN;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $username;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\OneToOne(inversedBy: 'screenUser', targetEntity: Screen::class)]
    private Screen $screen;

    #[ORM\Column(nullable: true)]
    private ?int $releaseTimestamp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $releaseVersion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $latestRequest = null;

    #[ORM\Column(nullable: true)]
    private ?array $clientMeta = null;

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = Roles::ROLE_USER;

        // guarantee every screen has ROLE_SCREEN
        $roles[] = self::ROLE_SCREEN;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.
     *
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.
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

    public function getScreen(): Screen
    {
        return $this->screen;
    }

    public function setScreen(Screen $screen): self
    {
        $this->screen = $screen;

        return $this;
    }

    public function getActiveTenant(): Tenant
    {
        return $this->getScreen()->getTenant();
    }

    public function setActiveTenant(Tenant $activeTenant): self
    {
        if ($this->getScreen()->getTenant() !== $activeTenant) {
            throw new \InvalidArgumentException('Active Tenant cannot be set. User does not have access to Tenant: '.$activeTenant->getTenantKey());
        }

        return $this;
    }

    public function getTenants(): Collection
    {
        return new ArrayCollection([$this->getScreen()->getTenant()]);
    }

    public function getUserRoleTenants(): Collection
    {
        $userRoleTenant = new \stdClass();
        $userRoleTenant->tenantKey = $this->getScreen()->getTenant()->getTenantKey();
        $userRoleTenant->title = $this->getScreen()->getTenant()->getTitle();
        $userRoleTenant->description = $this->getScreen()->getTenant()->getDescription();
        $userRoleTenant->roles = $this->getRoles();

        return new ArrayCollection([$userRoleTenant]);
    }

    public function getBlamableIdentifier(): string
    {
        return 'Screen-'.$this->screen->getId()?->jsonSerialize();
    }

    public function getReleaseTimestamp(): ?int
    {
        return $this->releaseTimestamp;
    }

    public function setReleaseTimestamp(?int $releaseTimestamp): static
    {
        $this->releaseTimestamp = $releaseTimestamp;

        return $this;
    }

    public function getReleaseVersion(): ?string
    {
        return $this->releaseVersion;
    }

    public function setReleaseVersion(?string $releaseVersion): static
    {
        $this->releaseVersion = $releaseVersion;

        return $this;
    }

    public function getLatestRequest(): ?\DateTimeInterface
    {
        return $this->latestRequest;
    }

    public function setLatestRequest(?\DateTimeInterface $latestRequest): static
    {
        $this->latestRequest = $latestRequest;

        return $this;
    }

    public function getClientMeta(): ?array
    {
        return $this->clientMeta;
    }

    public function setClientMeta(?array $clientMeta): static
    {
        $this->clientMeta = $clientMeta;

        return $this;
    }
}
