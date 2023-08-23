<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenGroup as ScreenGroupDTO;
use App\Entity\Tenant\ScreenGroup;

class ScreenGroupOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenGroupDTO
    {
        /** @var ScreenGroup $object */
        $output = new ScreenGroupDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->modified = $object->getModifiedAt();
        $output->created = $object->getCreatedAt();
        $output->modifiedBy = $object->getModifiedBy();
        $output->createdBy = $object->getCreatedBy();

        $iri = $this->iriConverter->getIriFromItem($object);
        $output->campaigns = $iri.'/campaigns';
        $output->screens = $iri.'/screens';

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenGroupDTO::class === $to && $data instanceof ScreenGroup;
    }
}
