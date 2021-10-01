<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Media as MediaDTO;
use App\Entity\Media;
use Symfony\Component\HttpFoundation\RequestStack;

class MediaOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($media, string $to, array $context = []): MediaDTO
    {
        /** @var Media $media */
        $output = new MediaDTO();
        $output->title = $media->getTitle();
        $output->description = $media->getDescription();
        $output->license = $media->getLicense();
        $output->created = $media->getCreatedAt();
        $output->modified = $media->getUpdatedAt();
        $output->createdBy = $media->getCreatedBy();
        $output->modifiedBy = $media->getModifiedBy();
        $output->assets = [
            'type' => $media->getMimeType(),
            'uri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().$media->getUrl(),
            'dimensions' => [
                'height' => $media->getHeight(),
                'width' => $media->getWidth(),
            ],
            'sha' => $media->getSha(),
            'size' => $media->getSize(),
        ];

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
