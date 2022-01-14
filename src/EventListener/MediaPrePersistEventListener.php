<?php

namespace App\EventListener;

use App\Entity\Media;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaPrePersistEventListener
{
    public function __construct(
        private StorageInterface $storage
    ) {
    }

    /**
     * Add metadata about the uploaded media.
     */
    public function postPersist(Media $media, LifecycleEventArgs $event): void
    {
        $file = $this->getPath($media);
        $info = getimagesize($file);

        $media->setMimeType(mime_content_type($file));

        if ($info !== false) {
            $media->setWidth($info[0]);
            $media->setHeight($info[1]);
        }

        $media->setSize(filesize($file));
        $media->setSha(sha1_file($file));

        $em = $event->getObjectManager();
        $em->persist($media);
        $em->flush($media);
    }

    private function getPath(Media $media): string
    {
        return $this->storage->resolvePath($media, 'file');
    }
}
