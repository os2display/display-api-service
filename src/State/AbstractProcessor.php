<?php

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
        private readonly ProcessorInterface $removeProcessor
    ) {}

    /**
     * {@inheritdoc}
     *
     * @return T
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $data = $this->fromInput($data, $operation, $uriVariables, $context);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * @return T
     */
    abstract protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): object;

    /**
     * Load previous object if any.
     *
     * This is needed to get an objet handled by entity manager.
     *
     * @param $object
     * @param array $context
     *
     * @return mixed|object|null
     */
    protected function loadPrevious($object, array $context)
    {
        try {
            if (($previous = $context['previous_data'] ?? null) && is_a($previous, $object::class)) {
                $repository = $this->entityManager->getRepository($object::class);
                if (method_exists($previous, 'getId')) {
                    $object = $repository->find($previous->getId());
                }
            }
        } catch (\Throwable) {
        }

        return $object;
    }
}
