<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenGroupCampaign as ScreenGroupCampaignDTO;
use App\Entity\Tenant\ScreenGroupCampaign;

class ScreenGroupCampaignOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($screenGroupCampaign, string $to, array $context = []): ScreenGroupCampaignDTO
    {
        /** @var ScreenGroupCampaign $screenGroupCampaign */
        $output = new ScreenGroupCampaignDTO();
        $output->campaign = $screenGroupCampaign->getCampaign();
        $output->screenGroup = $screenGroupCampaign->getScreenGroup();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenGroupCampaignDTO::class === $to && $data instanceof ScreenGroupCampaign;
    }
}
