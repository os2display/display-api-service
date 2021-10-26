<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\OutputInterface;
use App\Dto\PublishedInterface;
use App\Entity\EntityPublishedInterface;
use App\Entity\EntitySharedInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

abstract class AbstractOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private StorageInterface $storage
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): object
    {
        /** @psalm-suppress InvalidStringClass */
        $outputDto = new $to();

        $this->populateShared($object, $outputDto);
        $this->populatePublished($object, $outputDto);

        return $outputDto;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function supportsTransformation($data, string $to, array $context = []): bool;

    private function populateShared(object $object, object $outputDto): void
    {
        if ($object instanceof EntitySharedInterface && $outputDto instanceof OutputInterface) {
            $outputDto->setTitle($object->getTitle());
            $outputDto->setDescription($object->getDescription());
            $outputDto->setCreated($object->getCreatedAt());
            $outputDto->setModified($object->getUpdatedAt());
            $outputDto->setCreatedBy($object->getCreatedBy());
            $outputDto->setModifiedBy($object->getModifiedBy());
        }
    }

    private function populatePublished(object $object, object $outputDto): void
    {
        if ($object instanceof EntityPublishedInterface && $outputDto instanceof PublishedInterface) {
            $outputDto->setPublishedFrom($object->getPublishedFrom());
            $outputDto->setPublishedTo($object->getPublishedTo());
        }
    }
}
