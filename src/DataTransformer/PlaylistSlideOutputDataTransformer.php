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
    public function transform($object, string $to, array $context = []): PlaylistSlideDTO
    {
        /** @var PlaylistSlide $object */
        $output = new PlaylistSlideDTO();
        $output->slide = $object->getSlide();
        $output->playlist = $object->getPlaylist();
        $output->weight = $object->getWeight();

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
