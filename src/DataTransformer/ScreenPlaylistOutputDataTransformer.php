<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenPlaylist as ScreenPlaylistDTO;
use App\Entity\ScreenPlaylist;

class ScreenPlaylistOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($screenPlaylist, string $to, array $context = []): ScreenPlaylistDTO
    {
        /** @var ScreenPlaylist $screenPlaylist */
        $output = new ScreenPlaylistDTO();
        $output->playlist = $screenPlaylist->getPlaylist();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenPlaylistDTO::class === $to && $data instanceof ScreenPlaylist;
    }
}
