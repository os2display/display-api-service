<?php

namespace App\DataTransformer;

use App\Entity\ScreenGroup;

final class ScreenGroupInputInputDataTransformer extends AbstractInputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenGroup
    {
        return parent::transform($object, $to, $context);
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
