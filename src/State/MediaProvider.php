<?php

namespace App\State;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\Media;
use App\Entity\User;
use App\Exceptions\ItemDataProviderException;
use App\Repository\MediaRepository;
use App\Repository\PlaylistSlideRepository;
use App\Repository\SlideRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * A Media state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Media
 */
final class MediaProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly SlideRepository $slideRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $itemExtensions
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof Get) {
            return $this->provideItem(Media::class, $uriVariables['id'], $operation, $context);
        }

        return null;
    }

    private function provideItem(string $resourceClass, $id, Operation $operation, array $context): ?Media
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();

        /** @var User $user */
        $tenant = $user->getActiveTenant();

        if (!$id instanceof Ulid) {
            throw new ItemDataProviderException('Id should be of a Ulid');
        }

        $mediaUlid = $this->validationUtils->validateUlid($id->jsonSerialize());

        $identifiers = ['id' => $id];

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->mediaRepository->getById($mediaUlid);

        // Filter the query-builder with tenant extension
        foreach ($this->itemExtensions as $extension) {
            if ($extension instanceof QueryItemExtensionInterface) {
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operation, $context);
            }
        }

        // Get result. If there is a result this is returned.
        $media = $queryBuilder->getQuery()->getResult();
        if (0 === count($media)) {
            $media = null;
        } else {
            $media = $media[0];
        }

        // If there is not a result, shared playlists should be checked.
        if (is_null($media)) {
            $connectedSlides = $this->slideRepository->getSlidesByMedia($mediaUlid)->getQuery()->getResult();
            foreach ($connectedSlides as $slide) {
                $playlists = $this->playlistSlideRepository->getPlaylistsFromSlideId($slide->getId())->getQuery()->getResult();
                foreach ($playlists as $playlist) {
                    if (in_array($tenant, $playlist->getPlaylist()->getTenants()->toArray())) {
                        $media = $this->mediaRepository->find($mediaUlid);
                        break 2;
                    }
                }
            }
        }

        return $media;
    }
}
