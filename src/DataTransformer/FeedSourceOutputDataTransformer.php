<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\FeedSource as FeedSourceDTO;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Service\FeedService;

class FeedSourceOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private FeedService $feedService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): FeedSourceDTO
    {
        /** @var FeedSource $object */
        $output = new FeedSourceDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->feedType = $object->getFeedType();
        $output->supportedFeedOutputType = $object->getSupportedFeedOutputType();

        $output->feeds = $object->getFeeds()->map(function (Feed $feed) {
            return $this->iriConverter->getIriFromItem($feed);
        })->toArray();

        $output->admin = $this->feedService->getAdminFormOptions($object);

        // Do not expose secrets.

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
