<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly ?AbstractProvider $provider = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $data = $this->fromInput($data, $operation, $uriVariables, $context);
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $this->toOutput($result);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): mixed
    {
        return $object;
    }

    public function toOutput(object $object): object
    {
        return null !== $this->provider ? $this->provider->toOutput($object) : $object;
    }

    /**
     * Load previous object if any.
     *
     * This is needed to get an object handled by entity manager.
     *
     * @template T
     *
     * @param T $object
     * @param array $context
     *
     * @return T|object|null
     */
    protected function loadPrevious(mixed $object, array $context): mixed
    {
        if ($previous = $context['previous_data'] ?? null) {
            $repository = $this->entityManager->getRepository($object::class);
            $id = method_exists($previous, 'getId')
                ? $previous->getId()
                : ($previous->id ?? null);
            $object = $repository->find($id);
        }

        return $object;
    }
}
