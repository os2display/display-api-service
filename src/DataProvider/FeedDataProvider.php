<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Feed;
use App\Entity\User;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Service\FeedService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

final class FeedDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private Security $security,
        private PlaylistSlideRepository $playlistSlideRepository,
        private FeedRepository $feedRepository,
        private FeedService $feedService,
        private LoggerInterface $logger,
        private iterable $itemExtensions = []
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Feed::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?JsonResponse
    {
        if (!$id instanceof Ulid) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();

        // Create a query builder, as the tenant filter works on query builders.
        $queryBuilder = $this->feedRepository->getById($id);

        $identifiers = ['id' => $id];

        // Filter the query builder with tenant extension.
        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
        }

        // Get result. If there is a result this is returned.
        try {
            $feed = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }

        // If there is not a result, shared playlists should be checked.
        if (is_null($feed)) {
            $user = $this->security->getUser();
            if (!is_null($user)) {
                /** @var User $user */
                $tenant = $user->getActiveTenant();

                $notAccessibleFeed = $this->feedRepository->find($id);
                if (!is_null($notAccessibleFeed)) {
                    $slide = $notAccessibleFeed->getSlide();
                    $slideId = $slide?->getId();
                    if (!is_null($slideId)) {
                        $playlists = $this->playlistSlideRepository->getPlaylistsFromSlideId($slideId)->getQuery()->getResult();
                        foreach ($playlists as $playlist) {
                            if (in_array($tenant, $playlist->getPlaylist()->getTenants()->toArray())) {
                                $feed = $notAccessibleFeed;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!is_null($feed)) {
            try {
                if ('get' === $operationName || 'get_feed_data' === $operationName) {
                    return new JsonResponse($this->feedService->getData($feed), 200);
                }
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                $this->logger->error(sprintf('Communication error "%s"', $e->getMessage()));
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Feed data error. ID: %s, MESSAGE: %s', $feed->getId()->jsonSerialize(), $e->getMessage()));
            }
        }

        // Null returned for data provider will result in a 404 response from API platform.
        return null;
    }
}
