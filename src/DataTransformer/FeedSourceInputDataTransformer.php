<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Dto\FeedSourceInput;
use App\Entity\Tenant\FeedSource;

final class FeedSourceInputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): FeedSource
    {
        $feedSource = new FeedSource();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $feedSource = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var FeedSourceInput $object */
        empty($object->title) ?: $feedSource->setTitle($object->title);
        empty($object->description) ?: $feedSource->setDescription($object->description);
        empty($object->createdBy) ?: $feedSource->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $feedSource->setModifiedBy($object->modifiedBy);
        empty($object->secrets) ?: $feedSource->setSecrets($object->secrets);
        empty($object->feedType) ?: $feedSource->setFeedType($object->feedType);
        empty($object->supportedFeedOutputType) ?: $feedSource->setSupportedFeedOutputType($object->supportedFeedOutputType);

        return $feedSource;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof FeedSource) {
            return false;
        }

        return FeedSource::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
