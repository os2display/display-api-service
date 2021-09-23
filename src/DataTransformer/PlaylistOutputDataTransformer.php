<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Playlist as PlaylistDTO;
use App\Entity\Playlist;

class PlaylistOutputDataTransformer implements DataTransformerInterface
{
    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    public function transform($playlist, string $to, array $context = [])
    {
        /** @var Playlist $playlist */
        $output = new PlaylistDTO();
        $output->title = $playlist->getTitle();
        $output->description = $playlist->getDescription();
        $output->created = $playlist->getCreatedAt();
        $output->modified = $playlist->getUpdatedAt();
        $output->createdBy = $playlist->getCreatedBy();
        $output->modifiedBy = $playlist->getModifiedBy();
        $output->slides = '/v1/playlists/'.$playlist->getId().'/slides';

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistDTO::class === $to && $data instanceof Playlist;
    }
}
