<?php

namespace App\Service;

use App\Entity\ScreenUser;
use App\Entity\Tenant;
use App\Entity\Tenant\Interactive;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveException;
use App\Interactive\InteractionRequest;
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

    /**
     * Create InteractionRequest from the request body.
     *
     * @param array $requestBody The request body from the http request.
     */
    public function parseRequestBody(array $requestBody): InteractionRequest
    {
        $implementationClass = $requestBody['implementationClass'];
        $action = $requestBody['action'];
        $data = $requestBody['data'];

        // TODO: Test for required.

        return new InteractionRequest($implementationClass, $action, $data);
    }

    /**
     * Perform the given InteractionRequest with the given Slide.
     *
     * @throws InteractiveException
     */
    public function performAction(Slide $slide, InteractionRequest $interactionRequest): array
    {
        $implementationClass = $interactionRequest->implementationClass;

        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof ScreenUser && !$currentUser instanceof User) {
            throw new InteractiveException("User is not supported");
        }

        $tenant = $currentUser->getActiveTenant();

        $interactive = $this->getInteractive($tenant, $implementationClass);

        if ($interactive === null) {
            throw new InteractiveException("Interactive not found");
        }

        $interactiveImplementation = $this->getImplementation($interactive->getImplementationClass());

        return $interactiveImplementation->performAction($slide, $interactionRequest);
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

    /**
     * @throws InteractiveException
     */
    public function getImplementation(?string $implementationClass): InteractiveInterface
    {
        $asArray = [...$this->interactiveImplementations];
        $interactiveImplementations = array_filter($asArray, fn($implementation) => $implementation::class === $implementationClass);

        if (count($interactiveImplementations) === 0) {
            throw new InteractiveException("Interactive implementation class not found");
        }

        return $interactiveImplementations[0];
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
