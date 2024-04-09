<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Feed as FeedDTO;
use App\Entity\Tenant\Feed;
use App\Entity\User;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Service\FeedService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * A Feed state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Feed
 */
final class FeedProvider extends AbstractProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly FeedRepository $feedRepository,
        private readonly FeedService $feedService,
        private readonly iterable $itemExtensions,
        private readonly SlideProvider $slideProvider,
        private readonly FeedSourceProvider $feedSourceProvider,
        ProviderInterface $collectionProvider
    ) {
        parent::__construct($collectionProvider, $this->feedRepository);
    }

    public function toOutput(object $object): object
    {
        assert($object instanceof Feed);

        $id = $object->getId();
        $modifiedAt = $object->getModifiedAt();
        $slide = $object->getSlide();
        $feedSource = $object->getFeedSource();

        assert(null !== $id);
        assert(null !== $modifiedAt);
        assert(null !== $slide);
        assert(null !== $feedSource);

        $output = new FeedDTO();
        $output->id = $id;
        $output->created = $object->getCreatedAt();
        $output->modified = $modifiedAt;
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->setRelationsChecksum($object->getRelationsChecksum());

        $output->configuration = $object->getConfiguration();
        $output->slide = $this->slideProvider->toOutput($slide);
        $output->feedSource = $this->feedSourceProvider->toOutput($feedSource);
        $output->feedUrl = $this->feedService->getRemoteFeedUrl($object);

        return $output;
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $resourceClass = Feed::class;
        $id = $uriVariables['id'] ?? null;
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

        return $feed;
    }
}
