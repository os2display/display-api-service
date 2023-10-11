<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\ScreenGroup;
use App\Repository\ScreenGroupRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

class ScreenGroupsScreensProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ScreenGroupRepository $screenGroupRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return $this->provideCollection(ScreenGroup::class, $operation, $uriVariables, $context);
        }

        return null;
    }

    public function provideCollection(string $resourceClass, Operation $operation, array $uriVariables, array $context): Paginator
    {
        $id = $uriVariables['id'] ?? '';
        $queryNameGenerator = new QueryNameGenerator();
        $screenUlid = $this->validationUtils->validateUlid($id);

        $queryBuilder = $this->screenGroupRepository->getScreenGroupsByScreenId($screenUlid);

        // Filter the query-builder with tenant extension.
        foreach ($this->collectionExtensions as $extension) {
            if ($extension instanceof QueryCollectionExtensionInterface) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $request->query?->get('itemsPerPage') ?? 10;
        $page = $request->query?->get('page') ?? 1;
        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
