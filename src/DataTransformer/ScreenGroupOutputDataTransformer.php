<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ScreenGroup as ScreenGroupDTO;
use App\Entity\Tenant\ScreenGroup;

class ScreenGroupOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($screenGroup, string $to, array $context = []): ScreenGroupDTO
    {
        /** @var ScreenGroup $screenGroup */
        $output = new ScreenGroupDTO();
        $output->title = $screenGroup->getTitle();
        $output->description = $screenGroup->getDescription();
        $output->modified = $screenGroup->getModifiedAt();
        $output->created = $screenGroup->getCreatedAt();
        $output->modifiedBy = $screenGroup->getModifiedBy();
        $output->createdBy = $screenGroup->getCreatedBy();

        $iri = $this->iriConverter->getIriFromItem($screenGroup);
        $output->campaigns = $iri.'/campaigns';

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenGroupDTO::class === $to && $data instanceof ScreenGroup;
    }
}
