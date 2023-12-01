<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Dto\PlaylistScreenRegion as PlaylistScreenRegionDTO;
use App\Entity\Tenant\PlaylistScreenRegion;
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
final class PlaylistScreenRegionProvider extends AbstractProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $collectionExtensions,
        private readonly PlaylistProvider $playlistProvider
    ) {}

    protected function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        $resourceClass = PlaylistScreenRegion::class;
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

    public function toOutput(object $object): PlaylistScreenRegionDTO
    {
        assert($object instanceof PlaylistScreenRegion);
        $output = new PlaylistScreenRegionDTO();
        $output->id = $object->getId();
        $output->playlist = $this->playlistProvider->toOutput($object->getPlaylist());
        $output->weight = $object->getWeight();

        return $output;
    }
}
