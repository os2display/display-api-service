<?php

namespace App\Service;

use App\Entity\ScreenUser;
use App\Entity\Tenant;
use App\Entity\Tenant\Interactive;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveException;
use App\Interactive\Interaction;
use App\Interactive\InteractiveInterface;
use App\Repository\InteractiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service for handling Slide interactions.
 */
readonly class InteractiveService
{
    public function __construct(
        /** @var array<InteractiveInterface> $interactives */
        private iterable               $interactiveImplementations,
        private InteractiveRepository  $interactiveRepository,
        private EntityManagerInterface $entityManager,
        private Security               $security,
    ) {
    }

    public function parseRequestBody(array $requestBody): Interaction
    {
        $implementationClass = $requestBody['implementationClass'];
        $action = $requestBody['action'];
        $data = $requestBody['data'];

        // TODO: Test for required.

        return new Interaction($implementationClass, $action, $data);
    }

    /**
     * @throws InteractiveException
     */
    public function performAction(Slide $slide, Interaction $interaction): array
    {
        $implementationClass = $interaction->implementationClass;

        $currentUser = $this->security->getUser();

        // TODO: Remove standard user from check.
        if (!$currentUser instanceof ScreenUser && !$currentUser instanceof User) {
            throw new InteractiveException("User is not supported");
        }

        $tenant = $currentUser->getActiveTenant();

        $interactive = $this->getInteractive($tenant, $implementationClass);

        if ($interactive === null) {
            throw new InteractiveException("Interactive not found");
        }

        $asArray = [...$this->interactiveImplementations];
        $interactiveImplementations = array_filter($asArray, fn($implementation) => $implementation::class === $implementationClass);

        if (count($interactiveImplementations) === 0) {
            throw new InteractiveException("Interactive implementation class not found");
        }

        $interactiveImplementation = $interactiveImplementations[0];

        return $interactiveImplementation->performAction($slide, $interaction);
    }

    /**
     * Get configurable interactive.
     */
    public function getConfigurables(): array
    {
        $result = [];

        foreach ($this->interactiveImplementations as $interactiveImplementation) {
            $result[$interactiveImplementation::class] = $interactiveImplementation->getConfigOptions();
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
