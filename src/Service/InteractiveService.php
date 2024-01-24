<?php

namespace App\Service;

use App\Entity\Tenant;
use App\Entity\Tenant\Interactive;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Interactive\InteractiveInterface;
use App\Repository\InteractiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service for handling Slide interactions.
 */
class InteractiveService
{
    public function __construct(
        /** @var array<InteractiveInterface> $interactives */
        private readonly iterable $interactives,
        private readonly InteractiveRepository $interactiveRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function performAction(Slide $slide, array $requestBody): array
    {
        return [];
    }

    /**
     * Get configurable interactive.
     */
    public function getConfigurables(): array
    {
        $result = [];

        foreach ($this->interactives as $interactive) {
            $result[$interactive::class] = $interactive->getConfigOptions();
        }

        return $result;
    }

    public function getInteractive(Tenant $tenant, string $implementationClass): ?Interactive
    {
        return $this->interactiveRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);
    }

    public function saveConfiguration(Tenant $tenant, string $implementationClass, array $configuration): void
    {
        $entry = $this->interactiveRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);

        if ($entry === null) {
            $entry = new Interactive();
            $entry->setTenant($tenant);
            $entry->setImplementationClass($implementationClass);

            $this->entityManager->persist($entry);
        }

        $entry->setConfiguration($configuration);

        $this->entityManager->flush();
    }
}
