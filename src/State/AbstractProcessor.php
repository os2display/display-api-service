<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * {@inheritdoc}
     */
    public function process(mixed $object, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $entity = $this->fromInput($object, $operation, $uriVariables, $context);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @return T
     */
    abstract protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): object;
}
