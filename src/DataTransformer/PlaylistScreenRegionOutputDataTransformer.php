<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistScreenRegion as PlaylistScreenRegionDTO;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Exceptions\DataTransformerException;

class PlaylistScreenRegionOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): PlaylistScreenRegionDTO
    {

        /** @var PlaylistScreenRegion $object */
        $output = new PlaylistScreenRegionDTO();

        $playlist = $object->getPlaylist();

        if (null === $playlist) {
            throw new DataTransformerException('Playlist is null');
        }

        $output->playlist = $playlist;
        $output->weight = $object->getWeight();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistScreenRegionDTO::class === $to && $data instanceof PlaylistScreenRegion;
    }
}
