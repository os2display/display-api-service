<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Repository\PlaylistScreenRegionRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

final class PlaylistScreenRegionCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return PlaylistScreenRegion::class === $resourceClass && 'get' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $itemsPerPage = $this->requestStack->getCurrentRequest()->query?->get('itemsPerPage') ?? 10;
        $page = $this->requestStack->getCurrentRequest()->query?->get('page') ?? 1;
        $id = $this->requestStack->getCurrentRequest()->attributes?->get('id') ?? '';
        $regionId = $this->requestStack->getCurrentRequest()->attributes?->get('regionId') ?? '';

        $queryNameGenerator = new QueryNameGenerator();
        $screenUlid = $this->validationUtils->validateUlid($id);
        $regionUlid = $this->validationUtils->validateUlid($regionId);

        $queryBuilder = $this->playlistScreenRegionRepository->getPlaylistsByScreenRegion($screenUlid, $regionUlid);

        // Filter the query-builder with tenant extension.
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }

        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
