<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenGroup as ScreenGroupDTO;
use App\Entity\ScreenGroup;

class ScreenGroupOutputDataTransformer implements DataTransformerInterface
{
    public function transform($screenGroup, string $to, array $context = [])
    {
        /** @var ScreenGroup $screenGroup */
        $output = new ScreenGroupDTO();
        $output->title = $screenGroup->getTitle();
        $output->description = $screenGroup->getDescription();
        $output->modified = $screenGroup->getUpdatedAt();
        $output->created = $screenGroup->getCreatedAt();
        $output->modifiedBy = $screenGroup->getModifiedBy();
        $output->createdBy = $screenGroup->getCreatedBy();

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenGroupDTO::class === $to && $data instanceof ScreenGroup;
    }
}
