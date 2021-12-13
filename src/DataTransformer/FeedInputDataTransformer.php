<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\FeedInput;
use App\Dto\FeedSource;
use App\Entity\Feed;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\FeedSourceRepository;
use App\Repository\SlideRepository;
use App\Utils\IriHelperUtils;

final class FeedInputDataTransformer implements DataTransformerInterface
{
    public function __construct(private IriHelperUtils $iriHelperUtils, private SlideRepository $slideRepository, private FeedSourceRepository $feedSourceRepository) {}

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): Feed
    {
        $feed = new Feed();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $feed = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var FeedInput $data */
        empty($data->title) ?: $feed->setTitle($data->title);
        empty($data->description) ?: $feed->setDescription($data->description);
        empty($data->createdBy) ?: $feed->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $feed->setModifiedBy($data->modifiedBy);

        empty($data->configuration) ?: $feed->setConfiguration($data->configuration);

        if (!empty($data->slide)) {
            // Validate that theme IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->slide);

            // Try loading theme entity.
            $slide = $this->slideRepository->findOneBy(['id' => $ulid]);
            if (is_null($slide)) {
                throw new InvalidArgumentException('Unknown slide resource');
            }

            $feed->setSlide($slide);
        }

        if (!empty($data->feedSource)) {
            // Validate that theme IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->feedSource);

            // Try loading theme entity.
            $feedSource = $this->feedSourceRepository->findOneBy(['id' => $ulid]);
            if (is_null($feedSource)) {
                throw new InvalidArgumentException('Unknown feedSource resource');
            }

            $feed->setFeedSource($feedSource);
        }

        return $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Feed) {
            return false;
        }

        return Feed::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
