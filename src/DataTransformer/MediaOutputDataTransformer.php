<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Media as MediaDTO;
use App\Entity\Tenant\Media;
use App\Exceptions\DataTransformerException;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private StorageInterface $storage
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): MediaDTO
    {
        /** @var Media $object */
        $output = new MediaDTO();
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
