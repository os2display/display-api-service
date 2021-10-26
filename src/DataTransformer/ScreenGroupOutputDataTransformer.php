<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenGroup as ScreenGroupDTO;
use App\Entity\ScreenGroup;

class ScreenGroupOutputDataTransformer extends AbstractOutputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($screenGroup, string $to, array $context = []): ScreenGroupDTO
    {
        return parent::transform($screenGroup, $to, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenGroupDTO::class === $to && $data instanceof ScreenGroup;
    }
}
