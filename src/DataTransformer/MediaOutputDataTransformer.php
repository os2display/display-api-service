<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Media as MediaDTO;
use App\Entity\Tenant\Media;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private StorageInterface $storage,
        private CacheManager $imagineCacheManager,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($media, string $to, array $context = []): MediaDTO
    {
        $uri = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().$this->storage->resolveUri($media, 'file');

        $baseUrl = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

        /** @var Media $media */
        $output = new MediaDTO();
        $output->title = $media->getTitle();
        $output->description = $media->getDescription();
        $output->license = $media->getLicense();
        $output->created = $media->getCreatedAt();
        $output->modified = $media->getModifiedAt();
        $output->createdBy = $media->getCreatedBy();
        $output->modifiedBy = $media->getModifiedBy();
        $output->assets = [
            'type' => $media->getMimeType(),
            'uri' => $uri,
            'dimensions' => [
                'height' => $media->getHeight(),
                'width' => $media->getWidth(),
            ],
            'sha' => $media->getSha(),
            'size' => $media->getSize(),
        ];

        if (str_starts_with($media->getMimeType(), 'image/')) {
            $output->thumbnail = $this->imagineCacheManager->getBrowserPath($this->storage->resolveUri($media, 'file'), 'thumbnail');
        } elseif (str_starts_with($media->getMimeType(), 'video/')) {
            $output->thumbnail = $baseUrl.'/media/thumbnail_video.png';
        } else {
            $output->thumbnail = $baseUrl.'/media/thumbnail_other.png';
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return MediaDTO::class === $to && $data instanceof Media;
    }
}
