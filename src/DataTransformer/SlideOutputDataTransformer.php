<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Media;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

class SlideOutputDataTransformer extends AbstractOutputDataTransformer
{
    public function __construct(
        private RequestStack $requestStack,
        private StorageInterface $storage,
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($slide, string $to, array $context = []): SlideDTO
    {
        /** @var Slide $slide */
        $output = parent::transform($slide, $to, $context);

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromItem($slide->getTemplate()),
            'options' => $slide->getTemplateOptions(),
        ];

        $output->onPlaylists[] = $slide->getPlaylistSlides()->map(function (PlaylistSlide $playlistSlide) {
            return $this->iriConverter->getIriFromItem($playlistSlide->getPlaylist());
        });

        $output->media[] = $slide->getMedia()->map(function (Media $media) {
            return $this->iriConverter->getIriFromItem($media);
        });

        $output->duration = $slide->getDuration();
        $output->content = $slide->getContent();

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
