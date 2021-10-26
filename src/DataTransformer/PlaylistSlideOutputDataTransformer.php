<?php

namespace App\DataTransformer;

use App\Dto\PlaylistSlide as PlaylistSlideDTO;
use App\Entity\PlaylistSlide;

class PlaylistSlideOutputDataTransformer extends AbstractOutputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($playlistSlide, string $to, array $context = []): PlaylistSlideDTO
    {
        /** @var PlaylistSlide $playlistSlide */
        $output = parent::transform($playlistSlide, $to, $context);

        $output->slide = $playlistSlide->getSlide();
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
