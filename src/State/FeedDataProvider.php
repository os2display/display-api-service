<?php

namespace App\State;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedData;
use App\Entity\User;
use App\Exceptions\MissingFeedConfigurationException;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Service\FeedService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * A FeedData state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of FeedData
 */
final class FeedDataProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private PlaylistSlideRepository $playlistSlideRepository,
        private FeedRepository $feedRepository,
        private FeedService $feedService,
        private LoggerInterface $logger,
        private iterable $itemExtensions = []
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof Get) {
            return $this->provideItem(Feed::class, $uriVariables['id'], $operation, $context);
        }

        return null;
    }

    private function provideItem(string $resourceClass, $id, Operation $operation, array $context): ?JsonResponse
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
            if ($extension instanceof QueryItemExtensionInterface) {
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operation, $context);
            }
        }

        // Get result. If there is a result this is returned.
        try {
            $feed = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
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
            $feedId = $feed->getId();
            $feedIdReference = null === $feedId ? '0' : $feedId->jsonSerialize();
            try {
                return new JsonResponse($this->feedService->getData($feed), 200);
            } catch (MissingFeedConfigurationException $e) {
                $this->logger->error(sprintf('Missing configuration for feed with id "%s" with message "%s"', $feedIdReference, $e->getMessage()));
            } catch (\JsonException $e) {
                $this->logger->error(sprintf('JSON decode for feed with id "%s" with error "%s"', $feedIdReference, $e->getMessage()));
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
                $this->logger->error(sprintf('Communication error "%s"', $e->getMessage()));
            }
        }

        // Null returned for data provider will result in a 404 response from API platform.
        return null;
    }
}
