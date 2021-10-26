<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\InputInterface;
use App\Dto\PublishedInterface;
use App\Entity\EntityPublishedInterface;
use App\Entity\EntitySharedInterface;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        protected ValidationUtils $utils
    ) {
    }

    public function transform($object, string $to, array $context = [])
    {
        /** @psalm-suppress InvalidStringClass */
        $entity = new $to();

        $this->populateEntity($object, $entity, $context);

        return $entity;
    }

    abstract public function supportsTransformation($data, string $to, array $context = []): bool;

    private function populateEntity($data, $entity, array $context): Entity
    {
        if (array_key_exists(AbstractNormalizer::OBJECT_TO_POPULATE, $context)) {
            $entity = $context[AbstractNormalizer::OBJECT_TO_POPULATE];
        }

        if ($entity instanceof EntitySharedInterface && $data instanceof InputInterface) {
            $this->populateSharedInput($entity, $data);
        }

        if ($entity instanceof EntityPublishedInterface && $data instanceof PublishedInterface) {
            $this->populatePublished($entity, $data);
        }
    }

    private function populateSharedInput(EntitySharedInterface $entity, InputInterface $data): void
    {
        empty($data->getTitle()) ?: $entity->setTitle($data->getTitle());
        empty($data->getDescription()) ?: $entity->setDescription($data->getDescription());

        empty($data->getCreatedBy()) ?: $entity->setCreatedBy($data->getCreatedBy());
        empty($data->getModifiedBy()) ?: $entity->setModifiedBy($data->getModifiedBy());
    }

    private function populatePublished(EntityPublishedInterface $entity, PublishedInterface $data): void
    {
        empty($data->getPublishedFrom()) ?: $entity->setPublishedFrom($this->utils->validateDate($data->getPublishedFrom()));
        empty($data->getPublishedTo()) ?: $entity->setPublishedTo($this->utils->validateDate($data->getPublishedTo()));
    }
}
