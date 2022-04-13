<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Feed;
use App\Repository\FeedRepository;
use App\Repository\SlideRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

final class FeedDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(private Security $security, private SlideRepository $slideRepository, private FeedRepository $feedRepository, private FeedService $feedService, private ValidationUtils $validationUtils, private iterable $itemExtensions)
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
        $feed = $queryBuilder->getQuery()->getResult();
        if (0 === count($feed)) {
            $feed = null;
        } else {
            $feed = $feed[0];
        }

        // If there is not a result, shared playlists should be checked.
        if (is_null($feed)) {
            // Get all slides with feed on, to check if the requested feed is on one of these slides.
            $slidesWithFeed = $this->slideRepository->getSlidesWithFeedData()->getQuery()->getResult();
            foreach ($slidesWithFeed as $slide) {
                if ($slide->getFeed()->getId() == $id) {
                    // The requested feed is on a slide, now the playlists are fetched to
                    // check if one of them is shared with current tenant.
                    $playlists = $slide->getPlaylistSlides()->toArray();
                    foreach ($playlists as $playlist) {
                        if (in_array($tenant, $playlist->getPlaylist()->getTenants()->toArray())) {
                            $feed = $this->feedRepository->find($feedUlid);
                            break;
                        }
                    }
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
