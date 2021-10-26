<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistInput;
use App\Entity\Playlist;
use App\Utils\ValidationUtils;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PlaylistInputInputDataTransformer extends AbstractInputDataTransformer
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function transform($object, string $to, array $context = []): Playlist
    {
        $playlist = parent::transform($object, $to, $context);

        empty($object->schedule) ?: $object->schedule = $this->transformRRuleNewline($object->schedule);
        empty($object->schedule) ?: $playlist->setSchedule($this->utils->validateRRule($object->schedule));

        return $playlist;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Playlist) {
            return false;
        }

        return Playlist::class === $to && null !== ($context['input']['class'] ?? null);
    }

    private function transformRRuleNewline(string $rrule): string
    {
        $rrule = str_replace('\\n', PHP_EOL, $rrule);

        return str_replace('\n', PHP_EOL, $rrule);
    }
}
