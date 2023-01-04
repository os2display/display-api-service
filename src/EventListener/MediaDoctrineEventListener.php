<?php

namespace App\EventListener;

use App\Entity\Tenant\Media;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaDoctrineEventListener
{
    public function __construct(
        private StorageInterface $storage
    ) {}

    /**
     * Add metadata about the uploaded media.
     */
    public function postPersist(Media $media, LifecycleEventArgs $event): void
    {
        $file = $this->getPath($media);
        $info = getimagesize($file);

        $media->setMimeType(mime_content_type($file));

        if (false !== $info) {
            $media->setWidth($info[0]);
            $media->setHeight($info[1]);
        }

        $media->setSize(filesize($file));
        $media->setSha(sha1_file($file));

        $em = $event->getObjectManager();
        $em->persist($media);
        $em->flush($media);
    }

    public function preRemove(Media $media, LifecycleEventArgs $event): void
    {
        if (count($media->getSlides()) > 0) {
            throw new ConflictHttpException('Media cannot be removed since it is bound to one or more slides');
        }
    }

    private function getPath(Media $media): string
    {
        return $this->storage->resolvePath($media, 'file');
    }
}
