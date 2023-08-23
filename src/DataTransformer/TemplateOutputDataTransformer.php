<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Template as TemplateDTO;
use App\Entity\Template;

class TemplateOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): TemplateDTO
    {
        /** @var Template $object */
        $output = new TemplateDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->modified = $object->getModifiedAt();
        $output->created = $object->getCreatedAt();
        $output->modifiedBy = $object->getModifiedBy();
        $output->createdBy = $object->getCreatedBy();
        $output->resources = $object->getResources();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return TemplateDTO::class === $to && $data instanceof Template;
    }
}
