<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Media;
use App\Repository\MediaRepository;
use App\Repository\PlaylistSlideRepository;
use App\Repository\SlideRepository;
use App\Utils\ValidationUtils;
use Symfony\Component\Security\Core\Security;

final class MediaItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(private Security $security, private PlaylistSlideRepository $playlistSlideRepository, private SlideRepository $slideRepository, private MediaRepository $mediaRepository, private ValidationUtils $validationUtils, private iterable $itemExtensions = [])
    {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Media::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Media
    {
        $queryNameGenerator = new QueryNameGenerator();
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $mediaUlid = $this->validationUtils->validateUlid($id);

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->mediaRepository->getById($mediaUlid);

        // Filter the query-builder with tenant extension
        foreach ($this->itemExtensions as $extension) {
            $identifiers = ['id' => $id];
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
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
            $connectedSlides = $this->slideRepository->getSlidesByMedia($id)->getQuery()->getResult();
            foreach ($connectedSlides as $slide) {
                $playlists = $this->playlistSlideRepository->getPlaylistsFromSlideId($slide->getId())->getQuery()->getResult();
                foreach ($playlists as $playlist) {
                    if (in_array($tenant, $playlist->getPlaylist()->getTenants()->toArray())) {
                        $media = $this->mediaRepository->find($mediaUlid);
                        break;
                    }
                }
            }
        }

        return $media;
    }
}
