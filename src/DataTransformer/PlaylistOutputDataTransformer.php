<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Playlist as PlaylistDTO;
use App\Entity\Playlist;

class PlaylistOutputDataTransformer extends AbstractOutputDataTransformer
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
        $output = parent::transform($playlist, $to, $context);

        $schedule = $playlist->getSchedule();
        if (null !== $schedule) {
            $output->schedule = $this->transformRRuleNewline($schedule->rfcString(true));
        }

        $iri = $this->iriConverter->getIriFromItem($playlist);
        $output->slides = $iri.'/slides';

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistDTO::class === $to && $data instanceof Playlist;
    }

    private function transformRRuleNewline(string $rrule): string
    {
        return str_replace(PHP_EOL, '\\n', $rrule);
    }
}
