<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\PlaylistSlide;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistSlideRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

final class PlaylistSlideCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(private Security $security, private RequestStack $requestStack, private PlaylistSlideRepository $playlistSlideRepository, private PlaylistRepository $playlistRepository, private ValidationUtils $validationUtils, private iterable $collectionExtensions)
    {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return PlaylistSlide::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $itemsPerPage = (int) $this->requestStack->getCurrentRequest()->query->get('itemsPerPage', '10');
        $page = (int) $this->requestStack->getCurrentRequest()->query->get('page', '1');
        $id = $this->requestStack->getCurrentRequest()->attributes->get('id');
        $queryNameGenerator = new QueryNameGenerator();
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $playlistUlid = $this->validationUtils->validateUlid($id);

        // Get playlist to check shared-with-tenants
        $playlist = $this->playlistRepository->findOneBy(['id' => $playlistUlid]);
        $playlistSharedWithTenant = in_array($tenant, $playlist->getTenants()->toArray());
        $queryBuilder = $this->playlistSlideRepository->getPlaylistSlideRelationsFromPlaylistId($playlistUlid);

        if (!$playlistSharedWithTenant) {
            foreach ($this->collectionExtensions as $extensions) {
                foreach ($extensions as $extension) {
                    $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
                    if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                        return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
                    }
                }
            }
        }

        $firstResult = ($page - 1) * $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
