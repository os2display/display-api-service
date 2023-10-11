<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Dto\ScreenGroupInput;
use App\Entity\Tenant\ScreenGroup;

final class ScreenGroupInputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenGroup
    {
        $screenGroup = new ScreenGroup();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screenGroup = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ScreenGroupInput $object */
        empty($object->title) ?: $screenGroup->setTitle($object->title);
        empty($object->description) ?: $screenGroup->setDescription($object->description);
        empty($object->createdBy) ?: $screenGroup->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screenGroup->setModifiedBy($object->modifiedBy);

        return $screenGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof ScreenGroup) {
            return false;
        }

        return ScreenGroup::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
