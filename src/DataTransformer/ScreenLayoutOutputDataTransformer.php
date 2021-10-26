<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenLayout as ScreenLayoutDTO;
use App\Entity\ScreenLayout;

class ScreenLayoutOutputDataTransformer extends AbstractOutputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($screenLayout, string $to, array $context = []): ScreenLayoutDTO
    {
        /** @var ScreenLayout $screenLayout */
        $output = parent::transform($screenLayout, $to, $context);

        $output->title = $screenLayout->getTitle();
        $output->grid['rows'] = $screenLayout->getGridRows();
        $output->grid['columns'] = $screenLayout->getGridColumns();
        $output->regions = $screenLayout->getRegions();

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
