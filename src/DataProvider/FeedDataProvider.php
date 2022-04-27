<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Feed;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Repository\SlideRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

final class FeedDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(private Security $security, private SlideRepository $slideRepository, private PlaylistSlideRepository $playlistSlideRepository, private FeedRepository $feedRepository, private FeedService $feedService, private ValidationUtils $validationUtils, private iterable $itemExtensions = [])
    {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Feed::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): JsonResponse|Feed|NULL
    {
        $queryNameGenerator = new QueryNameGenerator();
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $feedUlid = $this->validationUtils->validateUlid($id);

        // Create a querybuilder, as the tenantfilter works on querybuilders.
        $queryBuilder = $this->feedRepository->getById($feedUlid);

        // Filter the querybuilder with tenantextension
        foreach ($this->itemExtensions as $extensions) {
            foreach ($extensions as $extension) {
                $identifiers = ['id' => $id];
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
                if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
                }
            }
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

        if ('get' === $operationName || is_null($feed)) {
            return $feed;
        } elseif ('get_feed_data' === $operationName) {
            return new JsonResponse($this->feedService->getData($feed), 200);
        }
    }
}
