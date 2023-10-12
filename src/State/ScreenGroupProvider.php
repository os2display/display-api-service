<?php

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\ScreenGroup as ScreenGroupDTO;
use App\Entity\Tenant\ScreenGroup;
use App\Repository\ScreenGroupRepository;

class ScreenGroupProvider extends AbstractProvider
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        ProviderInterface $collectionProvider,
        ScreenGroupRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    protected function toOutput(object $object): object
    {
        /** @var ScreenGroup $object */
        $output = new ScreenGroupDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->modified = $object->getModifiedAt();
        $output->created = $object->getCreatedAt();
        $output->modifiedBy = $object->getModifiedBy();
        $output->createdBy = $object->getCreatedBy();

        $iri = $this->iriConverter->getIriFromResource($object);
        $output->campaigns = $iri.'/campaigns';
        $output->screens = $iri.'/screens';

        return $output;
    }
}
