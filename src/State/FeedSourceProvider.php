<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\FeedSource as FeedSourceDTO;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\UnknownFeedTypeException;
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
            try {
                $feedType = $this->feedService->getFeedType($feedTypeString);
                $feedTypeSecretsArray = $feedType->getRequiredSecrets();

                foreach ($object->getSecrets() ?? [] as $key => $secret) {
                    if (isset($feedTypeSecretsArray[$key])) {
                        if (isset($feedTypeSecretsArray[$key]['exposeValue']) && true === $feedTypeSecretsArray[$key]['exposeValue']) {
                            $secrets[$key] = $secret;
                        }
                    }
                }
            } catch (UnknownFeedTypeException) { // @phpstan-ignore logging.silentCatch (deliberate read-degradation policy documented below; the write path still rejects unknown feed types)
                // Policy: reads degrade, writes reject. The stored feed type is no
                // longer registered (e.g. a type removed in 3.0.0), so expose no
                // secrets rather than failing the read with an HTTP 500 — the feed
                // source (and any feed embedding it) stays listable and removable.
                // The write path still rejects an unknown feedType: FeedSourceProcessor
                // lets UnknownFeedTypeException propagate, mapped to 422 via
                // exception_to_status in config/packages/api_platform.yaml.
                $secrets = [];
            }
        }

        $output->secrets = $secrets;

        return $output;
    }
}
