<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Feed;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

final class FeedDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private Security $security,
        private PlaylistSlideRepository $playlistSlideRepository,
        private FeedRepository $feedRepository,
        private FeedService $feedService,
        private ValidationUtils $validationUtils,
        private iterable $itemExtensions = []
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Feed::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?JsonResponse
    {
        $queryNameGenerator = new QueryNameGenerator();
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $feedUlid = $this->validationUtils->validateUlid($id);

        // Create a query builder, as the tenant filter works on query builders.
        $queryBuilder = $this->feedRepository->getById($feedUlid);

        // Filter the query builder with tenant extension.
        foreach ($this->itemExtensions as $extension) {
            $identifiers = ['id' => $id];
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
        }

        // Get result. If there is a result this is returned.
        $feed = $queryBuilder->getQuery()->getOneOrNullResult();

        // If there is not a result, shared playlists should be checked.
        if (is_null($feed)) {
            $notAccessibleFeed = $this->feedRepository->find($feedUlid);
            $slide = $notAccessibleFeed->getSlide();
            $playlists = $this->playlistSlideRepository->getPlaylistsFromSlideId($slide->getId())->getQuery()->getResult();
            foreach ($playlists as $ps) {
                if (in_array($tenant, $ps->getPlaylist()->getTenants()->toArray())) {
                    $feed = $notAccessibleFeed;
                    break;
                }
            }
        }

        if ('get' === $operationName) {
            return new JsonResponse($this->feedService->getData($feed), 200);
        } elseif ('get_feed_data' === $operationName) {
            return new JsonResponse($this->feedService->getData($feed), 200);
        }

        return null;
    }
}
