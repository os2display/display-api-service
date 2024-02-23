<?php

namespace App\Entity\Tenant;

use App\Repository\InteractiveRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: InteractiveRepository::class)]
class Interactive extends AbstractTenantScopedEntity
{
    #[Ignore]
    #[ORM\Column(nullable: true)]
    private ?array $configuration = null;

    #[ORM\Column(length: 255)]
    private ?string $implementationClass = null;

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getImplementationClass(): ?string
    {
        return $this->implementationClass;
    }

    public function setImplementationClass(string $implementationClass): static
    {
        $this->implementationClass = $implementationClass;

        return $this;
    }
}
