<?php

declare(strict_types=1);

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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Service for handling Slide interactions.
 */
readonly class InteractiveService
{
    public function __construct(
        /** @var array<InteractiveInterface> $interactives */
        private iterable $interactiveImplementations,
        private InteractiveRepository $interactiveRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Create InteractionRequest from the request body.
     *
     * @param array $requestBody the request body from the http request
     *
     * @throws InteractiveException
     */
    public function parseRequestBody(array $requestBody): InteractionRequest
    {
        $implementationClass = $requestBody['implementationClass'] ?? null;
        $action = $requestBody['action'] ?? null;
        $data = $requestBody['data'] ?? null;

        if (null === $implementationClass || null === $action || null === $data) {
            throw new InteractiveException('implementationClass, action and/or data not set.');
        }

        return new InteractionRequest($implementationClass, $action, $data);
    }

    /**
     * @TODO: Describe.
     *
     * @throws InteractiveException
     */
    public function performAction(UserInterface $user, Slide $slide, InteractionRequest $interactionRequest): array
    {
        if (!$user instanceof ScreenUser && !$user instanceof User) {
            throw new InteractiveException('User is not supported');
        }

        $tenant = $user->getActiveTenant();

        $implementationClass = $interactionRequest->implementationClass;

        $interactive = $this->getInteractive($tenant, $implementationClass);

        if (null === $interactive) {
            throw new InteractiveException('Interactive not found');
        }

        $interactiveImplementation = $this->getImplementation($interactive->getImplementationClass());

        return $interactiveImplementation->performAction($user, $slide, $interactionRequest);
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
     * @TODO: Describe.
     *
     * @throws InteractiveException
     */
    public function getImplementation(?string $implementationClass): InteractiveInterface
    {
        $asArray = [...$this->interactiveImplementations];
        $interactiveImplementations = array_filter($asArray, fn ($implementation) => $implementation::class === $implementationClass);

        if (0 === count($interactiveImplementations)) {
            throw new InteractiveException('Interactive implementation class not found');
        }

        return $interactiveImplementations[0];
    }

    /**
     * @TODO: Describe.
     */
    public function getInteractive(Tenant $tenant, string $implementationClass): ?Interactive
    {
        return $this->interactiveRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @TODO: Describe.
     */
    public function saveConfiguration(Tenant $tenant, string $implementationClass, array $configuration): void
    {
        $entry = $this->interactiveRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);

        if (null === $entry) {
            $entry = new Interactive();
            $entry->setTenant($tenant);
            $entry->setImplementationClass($implementationClass);

            $this->entityManager->persist($entry);
        }

        $entry->setConfiguration($configuration);

        $this->entityManager->flush();
    }

    /**
     * @TODO: Describe.
     */
    public function getConfigOptions(): array
    {
        // TODO: Implement getConfigOptions() method.
        return [];
    }
}
