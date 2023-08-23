<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Exceptions\DataTransformerException;
use App\Service\FeedService;

class SlideOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private FeedService $feedService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): SlideDTO
    {
        /** @var Slide $object */
        $output = new SlideDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();

        $objectTemplate = $object->getTemplate();

        if (null === $objectTemplate) {
            throw new DataTransformerException('Slide template is null');
        }

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromItem($objectTemplate),
            'options' => $object->getTemplateOptions(),
        ];


        $objectTheme = $object->getTheme();

        if ($objectTheme) {
            $output->theme = $this->iriConverter->getIriFromItem($objectTheme);
        }

        $output->onPlaylists = $object->getPlaylistSlides()->map(function (PlaylistSlide $playlistSlide) {
            return $this->iriConverter->getIriFromItem($playlistSlide->getPlaylist());
        });

        $output->media = $object->getMedia()->map(function (Media $media) {
            return $this->iriConverter->getIriFromItem($media);
        });

        $output->duration = $object->getDuration();
        $output->published = [
            'from' => $object->getPublishedFrom(),
            'to' => $object->getPublishedTo(),
        ];
        $output->content = $object->getContent();

        $feed = $object->getFeed();

        if ($feed) {

            $feedSource = $feed->getFeedSource();

            if (null === $feedSource) {
                throw new DataTransformerException('Feed source is null');
            }

            $output->feed = [
                '@id' => $feed->getId(),
                'configuration' => $feed->getConfiguration(),
                'feedSource' => $this->iriConverter->getIriFromItem($feedSource),
                'feedUrl' => $this->feedService->getRemoteFeedUrl($feed),
            ];
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return SlideDTO::class === $to && $data instanceof Slide;
    }
}
