<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Screen;

class ScreenOutputDataTransformer implements DataTransformerInterface
{
    public function transform($screen, string $to, array $context = [])
    {
        /** @var Screen $screen */
        $output = new ScreenDTO();
        $output->title = $screen->getTitle();
        $output->description = $screen->getDescription();
        $output->created = $screen->getCreatedAt();
        $output->modified = $screen->getUpdatedAt();
        $output->createdBy = $screen->getCreatedBy();
        $output->modifiedBy = $screen->getModifiedBy();
        $output->dimensions = [
            'width' => $screen->getResolutionWidth(),
            'height' => $screen->getResolutionHeight(),
        ];
        $output->screenLayout = $screen->getScreenLayout();

        $t = $screen->getScreenLayout()->getUlid();

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenDTO::class === $to && $data instanceof Screen;
    }

}
