<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Campaign as CampaignDTO;
use App\Entity\Campaign;

class CampaignOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
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

        $layout = $campaign->getCampaignLayout();
        $output->layout = $this->iriConverter->getIriFromItem($layout);
        $campaignIri = $this->iriConverter->getIriFromItem($campaign);
        $output->inScreenGroups = $campaignIri.'/groups';

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
