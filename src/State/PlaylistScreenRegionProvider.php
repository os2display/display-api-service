<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Dto\PlaylistScreenRegion as PlaylistScreenRegionDTO;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Slide;
use App\Exceptions\DataTransformerException;
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
        private RequestStack $requestStack,
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
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

    protected function toOutput(object $object): PlaylistScreenRegionDTO
    {
        /** @var PlaylistScreenRegion $object */
        $output = new PlaylistScreenRegionDTO();

        $playlist = $object->getPlaylist();

        if (null === $playlist) {
            throw new DataTransformerException('Playlist is null');
        }

        $output->playlist = $playlist;
        $output->weight = $object->getWeight();

        return $output;
    }
}
