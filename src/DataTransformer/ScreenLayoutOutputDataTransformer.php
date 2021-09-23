<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenLayout as ScreenLayoutDTO;
use App\Entity\ScreenLayout;

class ScreenLayoutOutputDataTransformer implements DataTransformerInterface
{
    public function transform($screenLayout, string $to, array $context = [])
    {
        /** @var ScreenLayout $screenLayout */
        $output = new ScreenLayoutDTO();
        $output->title = $screenLayout->getTitle();
        $output->grid['rows'] = $screenLayout->getGridRows();
        $output->grid['columns'] = $screenLayout->getGridColumns();
        $output->regions = $screenLayout->getRegions();

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenLayoutDTO::class === $to && $data instanceof ScreenLayout;
    }
}
