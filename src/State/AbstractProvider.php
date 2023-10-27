<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

abstract class AbstractProvider implements ProviderInterface
{
    public function __construct(
        // @see https://api-platform.com/docs/core/state-providers/#hooking-into-the-built-in-state-provider
        private readonly ProviderInterface $collectionProvider,
        private readonly ServiceEntityRepositoryInterface $entityRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $collection = $this->provideCollection($operation, $uriVariables, $context);
            if ($collection instanceof PaginatorInterface) {
                // @see https://api-platform.com/docs/core/pagination/#pagination-for-custom-state-providers
                return new TraversablePaginator(
                    new \ArrayIterator(
                        array_map($this->toOutput(...), iterator_to_array($collection))
                    ),
                    $collection->getCurrentPage(),
                    $collection->getItemsPerPage(),
                    $collection->getTotalItems()
                );
            }
        } elseif ($operation instanceof Get || $operation instanceof Put) {
            if ($entity = $this->provideItem($operation, $uriVariables, $context)) {
                return $this->toOutput($entity);
            }
        } elseif ($operation instanceof Delete) {
            return $this->provideItem($operation, $uriVariables, $context);
        }

        return null;
    }

    protected function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        return $this->collectionProvider->provide($operation, $uriVariables, $context);
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        return $this->entityRepository->find($uriVariables['id']);
    }

    protected function toOutput(object $object): object
    {
        return $object;
    }
}
