<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Playlist as PlaylistDTO;
use App\Entity\Playlist;

class PlaylistOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($playlist, string $to, array $context = []): PlaylistDTO
    {
        /** @var Playlist $playlist */
        $output = new PlaylistDTO();
        $output->title = $playlist->getTitle();
        $output->description = $playlist->getDescription();
        $output->schedule = null !== $playlist->getSchedule() ? $playlist->getSchedule()->rfcString(true) : '';
        $output->created = $playlist->getCreatedAt();
        $output->modified = $playlist->getUpdatedAt();
        $output->createdBy = $playlist->getCreatedBy();
        $output->modifiedBy = $playlist->getModifiedBy();

        $iri = $this->iriConverter->getIriFromItem($playlist);
        $output->slides = $iri.'/slides';

        $output->published = [
            'from' => $playlist->getPublishedFrom(),
            'to' => $playlist->getPublishedTo(),
        ];

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistDTO::class === $to && $data instanceof Playlist;
    }
}
