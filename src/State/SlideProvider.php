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

    public function toOutput(object $object): SlideDTO
    {
        assert($object instanceof Slide);
        $output = new SlideDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->relationsModified = $object->getRelationsModified();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();

        $objectTemplate = $object->getTemplate();

        if (null === $objectTemplate) {
            throw new DataTransformerException('Slide template is null');
        }

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromResource($objectTemplate),
            'options' => $object->getTemplateOptions(),
        ];

        $objectTheme = $object->getTheme();

        if ($objectTheme) {
            $output->theme = $this->iriConverter->getIriFromResource($objectTheme);
        }

        $output->onPlaylists = $object->getPlaylistSlides()->map(fn (PlaylistSlide $playlistSlide) => $this->iriConverter->getIriFromResource($playlistSlide->getPlaylist()));

        $output->media = $object->getMedia()->map(fn (Media $media) => $this->iriConverter->getIriFromResource($media));

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
                'feedSource' => $this->iriConverter->getIriFromResource($feedSource),
                'feedUrl' => $this->feedService->getRemoteFeedUrl($feed),
            ];
        }

        return $output;
    }
}
