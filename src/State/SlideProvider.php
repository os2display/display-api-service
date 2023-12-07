<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Exceptions\DataTransformerException;
use App\Repository\SlideRepository;
use App\Service\FeedService;

class SlideProvider extends AbstractProvider
{
    public function __construct(
        private readonly SlideRepository $slideRepository,
        private readonly IriConverterInterface $iriConverter,
        private readonly FeedService $feedService,
        ProviderInterface $collectionProvider
    ) {
        parent::__construct($collectionProvider, $this->slideRepository);
    }

    public function toOutput(object $slide): SlideDTO
    {
        assert($slide instanceof Slide);
        $output = new SlideDTO();
        $output->id = $slide->getId();
        $output->title = $slide->getTitle();
        $output->description = $slide->getDescription();
        $output->created = $slide->getCreatedAt();
        $output->modified = $slide->getModifiedAt();
        $output->createdBy = $slide->getCreatedBy();
        $output->modifiedBy = $slide->getModifiedBy();

        $objectTemplate = $slide->getTemplate();

        if (null === $objectTemplate) {
            throw new DataTransformerException('Slide template is null');
        }

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromResource($objectTemplate),
            'options' => $slide->getTemplateOptions(),
        ];

        $objectTheme = $slide->getTheme();

        if ($objectTheme) {
            $output->theme = $this->iriConverter->getIriFromResource($objectTheme);
        }

        $output->onPlaylists = $slide->getPlaylistSlides()->map(fn (PlaylistSlide $playlistSlide) => $this->iriConverter->getIriFromResource($playlistSlide->getPlaylist()));

        $output->media = $slide->getMedia()->map(fn (Media $media) => $this->iriConverter->getIriFromResource($media));

        $output->duration = $slide->getDuration();
        $output->published = [
            'from' => $slide->getPublishedFrom(),
            'to' => $slide->getPublishedTo(),
        ];
        $output->content = $slide->getContent();

        $feed = $slide->getFeed();

        if ($feed) {
            $feedSource = $feed->getFeedSource();

            if (null === $feedSource) {
                throw new DataTransformerException('Feed source is null');
            }

            $output->feed = [
                '@id' => $feed->getId(),
                'configuration' => $feed->getConfiguration(),
                'feedSource' => $this->iriConverter->getIriFromResource($feedSource),
                'feedUrl' => $this->feedService->getRemoteFeedUrl($feed),
            ];
        }

        return $output;
    }
}
