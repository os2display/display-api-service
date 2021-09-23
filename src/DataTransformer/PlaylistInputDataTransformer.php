<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\PlaylistInput;
use App\Entity\Playlist;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PlaylistInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
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
        empty($data->published['from']) ?: $playlist->setPublishedFrom($this->validateDate($data->published['from']));
        empty($data->published['to']) ?: $playlist->setPublishedTo($this->validateDate($data->published['to']));

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

    /**
     * Validate date string.
     *
     * @TODO: Should be move into utility class and reused.
     *
     * @throws \Exception
     */
    private function validateDate(string $date): \DateTime
    {
        // @TODO: Move format string into configuration.
        $errors = $this->validator->validate($date, new Assert\DateTime('Y-m-d\TH:i:s\Z'));
        if (0 !== count($errors)) {
            throw new InvalidArgumentException('Date format not valid');
        }

        return new \DateTime($date);
    }
}
