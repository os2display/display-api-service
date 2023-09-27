<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistSlideRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

final class PlaylistSlideCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private PlaylistSlideRepository $playlistSlideRepository,
        private PlaylistRepository $playlistRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return PlaylistSlide::class === $resourceClass && 'getPlaylistSlide' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $itemsPerPage = $this->requestStack->getCurrentRequest()->query?->get('itemsPerPage') ?? 10;
        $page = $this->requestStack->getCurrentRequest()->query?->get('page') ?? 1;
        $id = $this->requestStack->getCurrentRequest()->attributes?->get('id') ?? '';
        $queryNameGenerator = new QueryNameGenerator();
        /** @var User $user */
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $playlistUlid = $this->validationUtils->validateUlid($id);

        // Get playlist to check shared-with-tenants
        $playlist = $this->playlistRepository->findOneBy(['id' => $playlistUlid]);
        $playlistSharedWithTenant = in_array($tenant, $playlist?->getTenants()->toArray());
        $queryBuilder = $this->playlistSlideRepository->getPlaylistSlideRelationsFromPlaylistId($playlistUlid);

        if (!$playlistSharedWithTenant) {
            // Filter the query-builder with tenant extension.
            foreach ($this->collectionExtensions as $extension) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            }
        }

        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
