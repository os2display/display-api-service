<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistInput;
use App\Entity\Playlist;
use App\Utils\Utils;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PlaylistInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private Utils $utils
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function transform($data, string $to, array $context = []): Playlist
    {
        $playlist = new Playlist();
        if (array_key_exists(AbstractNormalizer::OBJECT_TO_POPULATE, $context)) {
            $playlist = $context[AbstractNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var PlaylistInput $data */
        empty($data->title) ?: $playlist->setTitle($data->title);
        empty($data->description) ?: $playlist->setDescription($data->description);

        empty($data->schedule) ?: $data->schedule = $this->transformRRuleNewline($data->schedule);
        empty($data->schedule) ?: $playlist->setSchedule($this->utils->validateRRule($data->schedule));

        empty($data->createdBy) ?: $playlist->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $playlist->setModifiedBy($data->modifiedBy);
        empty($data->published['from']) ?: $playlist->setPublishedFrom($this->utils->validateDate($data->published['from']));
        empty($data->published['to']) ?: $playlist->setPublishedTo($this->utils->validateDate($data->published['to']));

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
