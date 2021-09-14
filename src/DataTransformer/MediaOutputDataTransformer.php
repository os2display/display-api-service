<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\MediaOutput;
use App\Entity\Media;

class MediaOutputDataTransformer implements DataTransformerInterface
{
    public function transform($media, string $to, array $context = [])
    {
        /** @var Media $media */
        $output = new MediaOutput();
        $output->title = $media->getTitle() ?? '';
        $output->description = $media->getDescription() ?? '';
        $output->license = $media->getLicense() ?? '';
        $output->created = $media->getCreatedAt();
        $output->modified = $media->getUpdatedAt();

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return MediaOutput::class === $to && $data instanceof Media;
    }

}