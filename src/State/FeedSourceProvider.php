<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\FeedSource as FeedSourceDTO;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Repository\FeedSourceRepository;
use App\Service\FeedService;

class FeedSourceProvider extends AbstractProvider
{
    public function __construct(
        private readonly IriConverterInterface $iriConverter,
        private readonly FeedService $feedService,
        ProviderInterface $collectionProvider,
        FeedSourceRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    public function toOutput(object $object): FeedSourceDTO
    {
        if (!$object instanceof FeedSource) {
            throw new \Exception('object must be instance of FeedSource');
        }

        $output = new FeedSourceDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->feedType = $object->getFeedType() ?? '';
        $output->supportedFeedOutputType = $object->getSupportedFeedOutputType() ?? '';

        $output->feeds = $object->getFeeds()->map(fn (Feed $feed) => $this->iriConverter->getIriFromResource($feed))->toArray();

        $output->admin = $this->feedService->getAdminFormOptions($object) ?? [];

        $secrets = [];

        $feedTypeString = $object->getFeedType();
        if (null !== $feedTypeString) {
            $feedType = $this->feedService->getFeedType($feedTypeString);
            $feedTypeSecretsArray = $feedType->getRequiredSecrets();

            foreach ($object->getSecrets() ?? [] as $key => $secret) {
                if (isset($feedTypeSecretsArray[$key])) {
                    if (isset($feedTypeSecretsArray[$key]['exposeValue']) && true === $feedTypeSecretsArray[$key]['exposeValue']) {
                        $secrets[$key] = $secret;
                    }
                }
            }
        }

        $output->secrets = $secrets;

        return $output;
    }
}
