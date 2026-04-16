<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\InteractiveSlideActionInput;
use App\Entity\Tenant;
use App\Entity\Tenant\InteractiveSlideConfig;
use App\Entity\Tenant\Slide;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\TooManyRequestsException;
use App\InteractiveSlide\InteractionSlideRequest;
use App\InteractiveSlide\InteractiveSlideInterface;
use App\Repository\InteractiveSlideRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for handling Slide interactions.
 */
readonly class InteractiveSlideService
{
    public function __construct(
        /** @var array<InteractiveSlideInterface> $interactives */
        private iterable $interactiveImplementations,
        private InteractiveSlideRepository $interactiveSlideRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Create InteractionRequest from the DTO.
     *
     * @throws BadRequestException
     */
    public function parseInteractiveSlideActionInput(InteractiveSlideActionInput $input): InteractionSlideRequest
    {
        if (null === $input->implementationClass || null === $input->action || null === $input->data) {
            throw new BadRequestException('implementationClass, action and/or data not set.');
        }

        return new InteractionSlideRequest($input->implementationClass, $input->action, $input->data);
    }

    /**
     * Perform an action for an interactive slide.
     *
     * @throws ConflictException
     * @throws BadRequestException
     * @throws NotAcceptableException
     * @throws TooManyRequestsException
     */
    public function performAction(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array
    {
        $implementationClass = $interactionRequest->implementationClass;

        $interactive = $this->getInteractiveSlideConfig($tenant, $implementationClass);

        if (null === $interactive) {
            throw new NotAcceptableException('Interactive slide config not found');
        }

        $interactiveImplementation = $this->getImplementation($interactive->getImplementationClass());

        return $interactiveImplementation->performAction($tenant, $slide, $interactionRequest);
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
     * Find the implementation class.
     *
     * @throws BadRequestException
     */
    public function getImplementation(?string $implementationClass): InteractiveSlideInterface
    {
        $asArray = [...$this->interactiveImplementations];
        $interactiveImplementations = array_filter($asArray, fn ($implementation) => $implementation::class === $implementationClass);

        if (0 === count($interactiveImplementations)) {
            throw new BadRequestException('Interactive implementation class not found');
        }

        return $interactiveImplementations[0];
    }

    /**
     * Get the interactive slide.
     */
    public function getInteractiveSlideConfig(Tenant $tenant, string $implementationClass): ?InteractiveSlideConfig
    {
        return $this->interactiveSlideRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Save configuration for a interactive slide.
     */
    public function saveConfiguration(Tenant $tenant, string $implementationClass, array $configuration): void
    {
        $entry = $this->interactiveSlideRepository->findOneBy([
            'implementationClass' => $implementationClass,
            'tenant' => $tenant,
        ]);

        if (null === $entry) {
            $entry = new InteractiveSlideConfig();
            $entry->setTenant($tenant);
            $entry->setImplementationClass($implementationClass);

            $this->entityManager->persist($entry);
        }

        $entry->setConfiguration($configuration);

        $this->entityManager->flush();
    }
}
