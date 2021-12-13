<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Feed as FeedDTO;
use App\Entity\Feed;

class FeedOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(private IriConverterInterface $iriConverter) {}

    /**
     * {@inheritdoc}
     */
    public function transform($feed, string $to, array $context = []): FeedDTO
    {
        /** @var Feed $feed */
        $output = new FeedDTO();
        $output->title = $feed->getTitle();
        $output->description = $feed->getDescription();
        $output->created = $feed->getCreatedAt();
        $output->modified = $feed->getUpdatedAt();
        $output->createdBy = $feed->getCreatedBy();
        $output->modifiedBy = $feed->getModifiedBy();

        $output->configuration = $feed->getConfiguration();
        $output->feedSource = $this->iriConverter->getIriFromItem($feed->getFeedSource());
        $output->slide = $this->iriConverter->getIriFromItem($feed->getSlide());

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return FeedDTO::class === $to && $data instanceof Feed;
    }
}
