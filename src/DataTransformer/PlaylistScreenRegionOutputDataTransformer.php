<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistScreenRegion as PlaylistScreenRegionDTO;
use App\Entity\Tenant\PlaylistScreenRegion;

class PlaylistScreenRegionOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($playlistScreenRegion, string $to, array $context = []): PlaylistScreenRegionDTO
    {
        /** @var PlaylistScreenRegion $playlistScreenRegion */
        $output = new PlaylistScreenRegionDTO();
        $output->playlist = $playlistScreenRegion->getPlaylist();
        $output->weight = $playlistScreenRegion->getWeight();

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
