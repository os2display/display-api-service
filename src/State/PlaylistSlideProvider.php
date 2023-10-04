<?php

namespace App\State;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistSlideRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Playlist slide state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Slide
 */
final class PlaylistSlideProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $collectionExtensions
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof GetCollection) {
            return $this->provideCollection(Slide::class, $operation, $uriVariables, $context);
        }

        return null;
    }

    public function provideCollection(string $resourceClass, Operation $operation, array $uriVariables, array $context): Paginator
    {
        $request = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $request->query?->get('itemsPerPage') ?? 10;
        $page = $request->query?->get('page') ?? 1;
        $id = $uriVariables['id'] ?? '';
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
                if ($extension instanceof QueryCollectionExtensionInterface) {
                    $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation,
                        $context);
                }
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
