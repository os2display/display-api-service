<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenCampaign as ScreenCampaignDTO;
use App\Entity\ScreenCampaign;

class ScreenCampaignOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($screenCampaign, string $to, array $context = []): ScreenCampaignDTO
    {
        /** @var ScreenCampaign $screenCampaign */
        $output = new ScreenCampaignDTO();
        $output->campaign = $screenCampaign->getCampaign();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenCampaignDTO::class === $to && $data instanceof ScreenCampaign;
    }
}
