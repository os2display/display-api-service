<?php

namespace App\Api\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\MediaOutput;
use App\Api\Model\Media;

final class MediaOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        /** @var Media $data */
        $output = new MediaOutput();
        $output->title = $data->title;
        $output->assets = $data->getAssets();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return MediaOutput::class === $to && $data instanceof Media;
    }

}
