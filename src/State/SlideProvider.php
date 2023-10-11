<?php

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Exceptions\DataTransformerException;
use App\Repository\SlideRepository;
use App\Service\FeedService;

class SlideProvider implements ProviderInterface
{
    public function __construct(
        // @see https://api-platform.com/docs/core/state-providers/#hooking-into-the-built-in-state-provider
        private readonly ProviderInterface $collectionProvider,
        private readonly SlideRepository $slideRepository,
        private readonly IriConverterInterface $iriConverter,
        private readonly FeedService $feedService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $collection = $this->collectionProvider->provide($operation, $uriVariables, $context);
            if ($collection instanceof PaginatorInterface) {
                // @see https://api-platform.com/docs/core/pagination/#pagination-for-custom-state-providers
                return new TraversablePaginator(
                    new \ArrayIterator(
                        array_map($this->toDto(...), iterator_to_array($collection))
                    ),
                    $collection->getCurrentPage(),
                    $collection->getItemsPerPage(),
                    $collection->getTotalItems()
                );
            }
        } elseif ($operation instanceof Get) {
            if ($slide = $this->slideRepository->find($uriVariables['id'])) {
                return $this->toDto($slide);
            }
        }

        return null;
    }

    private function toDto(Slide $slide): SlideDTO
    {
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

        $output->onPlaylists = $slide->getPlaylistSlides()->map(function (PlaylistSlide $playlistSlide) {
            return $this->iriConverter->getIriFromResource($playlistSlide->getPlaylist());
        });

        $output->media = $slide->getMedia()->map(function (Media $media) {
            return $this->iriConverter->getIriFromResource($media);
        });

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
