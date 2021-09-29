<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\ScreenGroupInput;
use App\Entity\ScreenGroup;

final class ScreenGroupInputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): ScreenGroup
    {
        $screenGroup = new ScreenGroup();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screenGroup = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ScreenGroupInput $data */
        empty($data->title) ?: $screenGroup->setTitle($data->title);
        empty($data->description) ?: $screenGroup->setDescription($data->description);
        empty($data->createdBy) ?: $screenGroup->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $screenGroup->setModifiedBy($data->modifiedBy);

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
