<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenLayout as ScreenLayoutDTO;
use App\Entity\ScreenLayout;

class ScreenLayoutOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenLayoutDTO
    {
        /** @var ScreenLayout $object */
        $output = new ScreenLayoutDTO();
        $output->title = $object->getTitle();
        $output->grid['rows'] = $object->getGridRows();
        $output->grid['columns'] = $object->getGridColumns();
        $output->regions = $object->getRegions();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenLayoutDTO::class === $to && $data instanceof ScreenLayout;
    }
}
