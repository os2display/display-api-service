<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Campaign as CampaignDTO;
use App\Entity\Campaign;

class CampaignOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($campaign, string $to, array $context = []): CampaignDTO
    {
        /** @var Campaign $campaign */
        $output = new CampaignDTO();
        $output->title = $campaign->getTitle();
        $output->description = $campaign->getDescription();
        $output->created = $campaign->getCreatedAt();
        $output->modified = $campaign->getUpdatedAt();
        $output->createdBy = $campaign->getCreatedBy();
        $output->modifiedBy = $campaign->getModifiedBy();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return CampaignDTO::class === $to && $data instanceof Campaign;
    }
}
