<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenCampaign as ScreenCampaignDTO;
use App\Entity\Tenant\ScreenCampaign;

class ScreenCampaignOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenCampaignDTO
    {
        /** @var ScreenCampaign $object */
        $output = new ScreenCampaignDTO();
        $output->campaign = $object->getCampaign();
        $output->screen = $object->getScreen();

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
