<?php

namespace App\Entity\Tenant;

use App\Repository\UserActivationCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserActivationCodeRepository::class)]
class UserActivationCode extends AbstractTenantScopedEntity
{
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $code;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private ?\DateTime $codeExpire;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $username;

    #[ORM\Column(type: Types::JSON)]
    private ?array $roles = [];

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCodeExpire(): ?\DateTime
    {
        return $this->codeExpire;
    }

    public function setCodeExpire(?\DateTime $codeExpire): void
    {
        $this->codeExpire = $codeExpire;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): void
    {
        $this->roles = $roles;
    }
}
