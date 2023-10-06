<?php

namespace App\State;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Slide;
use App\Repository\PlaylistScreenRegionRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Playlist slide state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of PlaylistScreenRegion
 */
final class PlaylistScreenRegionProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return $this->provideCollection(Slide::class, $operation, $uriVariables, $context);
        }

        return null;
    }

    public function provideCollection(string $resourceClass, Operation $operation, array $uriVariables, array $context): Paginator
    {
        $id = $uriVariables['id'] ?? '';
        $regionId = $this->requestStack->getCurrentRequest()->attributes?->get('regionId') ?? '';

        $queryNameGenerator = new QueryNameGenerator();
        $screenUlid = $this->validationUtils->validateUlid($id);
        $regionUlid = $this->validationUtils->validateUlid($regionId);

        $queryBuilder = $this->playlistScreenRegionRepository->getPlaylistsByScreenRegion($screenUlid, $regionUlid);

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
