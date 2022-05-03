<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistSlide as PlaylistSlideDTO;
use App\Entity\Tenant\PlaylistSlide;

class PlaylistSlideOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($playlistSlide, string $to, array $context = []): PlaylistSlideDTO
    {
        /** @var PlaylistSlide $playlistSlide */
        $output = new PlaylistSlideDTO();
        $output->slide = $playlistSlide->getSlide();
        $output->playlist = $playlistSlide->getPlaylist();
        $output->weight = $playlistSlide->getWeight();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistSlideDTO::class === $to && $data instanceof PlaylistSlide;
    }
}
