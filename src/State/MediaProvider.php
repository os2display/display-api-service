<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Media as MediaDTO;
use App\Entity\Tenant\Media;
use App\Entity\User;
use App\Exceptions\DataTransformerException;
use App\Exceptions\ItemDataProviderException;
use App\Repository\MediaRepository;
use App\Repository\PlaylistSlideRepository;
use App\Repository\SlideRepository;
use App\Utils\ValidationUtils;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * A Media state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Media
 */
final class MediaProvider extends AbstractProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly SlideRepository $slideRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly ValidationUtils $validationUtils,
        private RequestStack $requestStack,
        private StorageInterface $storage,
        private CacheManager $imagineCacheManager,
        private readonly iterable $itemExtensions,
        ProviderInterface $collectionProvider,
        MediaRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    protected function provideCollection(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): PaginatorInterface {
        $collection = parent::provideCollection($operation, $uriVariables, $context);

        // Convert the items
        return new TraversablePaginator(
            new \ArrayIterator(
                array_map(fn ($object) => $this->provideItem($operation, ['id' => method_exists($object, 'getId') ? $object->getId() : null], $context), iterator_to_array($collection))
            ),
            $collection->getCurrentPage(),
            $collection->getItemsPerPage(),
            $collection->getTotalItems()
        );
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();
        $resourceClass = Media::class;

        /** @var User $user */
        $tenant = $user->getActiveTenant();

        $id = $uriVariables['id'] ?? null;
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

    public function toOutput(object $object): object
    {
        /** @var Media $object */
        $output = new MediaDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->license = $object->getLicense();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();

        $currentRequest = $this->requestStack->getCurrentRequest();

        if (null === $currentRequest) {
            throw new DataTransformerException('Current request is null');
        }

        $baseUrl = $currentRequest->getSchemeAndHttpHost();

        $output->assets = [
            'type' => $object->getMimeType(),
            'uri' => $currentRequest->getSchemeAndHttpHost().$this->storage->resolveUri($object, 'file'),
            'dimensions' => [
                'height' => $object->getHeight(),
                'width' => $object->getWidth(),
            ],
            'sha' => $object->getSha(),
            'size' => $object->getSize(),
        ];

        $path = $this->storage->resolveUri($object, 'file');

        if (null === $path) {
            throw new DataTransformerException('Media path is null');
        }

        if (str_starts_with($object->getMimeType(), 'image/')) {
            $output->thumbnail = $this->imagineCacheManager->getBrowserPath($path, 'thumbnail');
        } elseif (str_starts_with($object->getMimeType(), 'video/')) {
            $output->thumbnail = $baseUrl.'/media/thumbnail_video.png';
        } else {
            $output->thumbnail = $baseUrl.'/media/thumbnail_other.png';
        }

        return $output;
    }
}
