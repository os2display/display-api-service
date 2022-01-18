<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Media;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use App\Service\FeedService;

class SlideOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter, private FeedService $feedService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($slide, string $to, array $context = []): SlideDTO
    {
        /** @var Slide $slide */
        $output = new SlideDTO();
        $output->title = $slide->getTitle();
        $output->description = $slide->getDescription();
        $output->created = $slide->getCreatedAt();
        $output->modified = $slide->getUpdatedAt();
        $output->createdBy = $slide->getCreatedBy();
        $output->modifiedBy = $slide->getModifiedBy();

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromItem($slide->getTemplate()),
            'options' => $slide->getTemplateOptions(),
        ];

        if ($slide->getTheme()) {
            $output->theme = $this->iriConverter->getIriFromItem($slide->getTheme());
        }

        $output->onPlaylists = $slide->getPlaylistSlides()->map(function (PlaylistSlide $playlistSlide) {
            return $this->iriConverter->getIriFromItem($playlistSlide->getPlaylist());
        });

        $output->media = $slide->getMedia()->map(function (Media $media) {
            return $this->iriConverter->getIriFromItem($media);
        });

        $output->duration = $slide->getDuration();
        $output->published = [
            'from' => $slide->getPublishedFrom(),
            'to' => $slide->getPublishedTo(),
        ];
        $output->content = $slide->getContent();

        if ($slide->getFeed()) {
            $feed = $slide->getFeed();
            $output->feed = [
                '@id' => $feed->getId(),
                'configuration' => $feed->getConfiguration(),
                'feedSource' => $this->iriConverter->getIriFromItem($feed->getFeedSource()),
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
