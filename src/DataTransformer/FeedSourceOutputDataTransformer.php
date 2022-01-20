<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\FeedSource as FeedSourceDTO;
use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;

class FeedSourceOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(private IriConverterInterface $iriConverter, private FeedService $feedService)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($feedSource, string $to, array $context = []): FeedSourceDTO
    {
        /** @var FeedSource $feedSource */
        $output = new FeedSourceDTO();
        $output->title = $feedSource->getTitle();
        $output->description = $feedSource->getDescription();
        $output->created = $feedSource->getCreatedAt();
        $output->modified = $feedSource->getUpdatedAt();
        $output->createdBy = $feedSource->getCreatedBy();
        $output->modifiedBy = $feedSource->getModifiedBy();
        $output->feedType = $feedSource->getFeedType();

        $output->feeds = $feedSource->getFeeds()->map(function (Feed $feed) {
            return $this->iriConverter->getIriFromItem($feed);
        })->toArray();

        $output->admin = $this->feedService->getAdminFormOptions($feedSource);

        // Do not expose secrets or configuration.

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return FeedSourceDTO::class === $to && $data instanceof FeedSource;
    }
}
