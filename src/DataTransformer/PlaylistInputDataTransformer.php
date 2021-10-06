<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\PlaylistInput;
use App\Entity\Playlist;
use App\Utils\ValidationUtils;

final class PlaylistInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private ValidationUtils $utils
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
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $playlist = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var PlaylistInput $data */
        empty($data->title) ?: $playlist->setTitle($data->title);
        empty($data->description) ?: $playlist->setDescription($data->description);
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
}
