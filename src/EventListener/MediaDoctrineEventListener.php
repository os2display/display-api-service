<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Tenant\Media;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaDoctrineEventListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly CacheManager $imageCacheManager,
    ) {}

    public function preRemove(Media $media, LifecycleEventArgs $event): void
    {
        if (count($media->getSlides()) > 0) {
            throw new ConflictHttpException('Media cannot be removed since it is bound to one or more slides');
        }
    }

    public function postRemove(Media $media): void
    {
        // Remove cached images from LiipImagineBundle once the row is deleted,
        // so a failed delete keeps them (#568).
        $this->imageCacheManager->remove($this->storage->resolveUri($media, 'file'));
    }
}
