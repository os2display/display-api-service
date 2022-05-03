<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\FeedSourceInput;
use App\Entity\Tenant\FeedSource;

final class FeedSourceInputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): FeedSource
    {
        $feedSource = new FeedSource();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $feedSource = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var FeedSourceInput $data */
        empty($data->title) ?: $feedSource->setTitle($data->title);
        empty($data->description) ?: $feedSource->setDescription($data->description);
        empty($data->createdBy) ?: $feedSource->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $feedSource->setModifiedBy($data->modifiedBy);
        empty($data->secrets) ?: $feedSource->setSecrets($data->secrets);
        empty($data->feedType) ?: $feedSource->setFeedType($data->feedType);
        empty($data->supportedFeedOutputType) ?: $feedSource->setSupportedFeedOutputType($data->supportedFeedOutputType);

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
