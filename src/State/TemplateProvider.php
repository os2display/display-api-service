<?php

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use App\Dto\Template as TemplateDTO;
use App\Entity\Template;
use App\Repository\TemplateRepository;

class TemplateProvider extends AbstractProvider
{
    public function __construct(
        private readonly ProviderInterface $collectionProvider,
        private readonly TemplateRepository $entityRepository
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    public function toOutput(object $object): TemplateDTO
    {
        assert($object instanceof Template);
        $output = new TemplateDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->modified = $object->getModifiedAt();
        $output->created = $object->getCreatedAt();
        $output->modifiedBy = $object->getModifiedBy();
        $output->createdBy = $object->getCreatedBy();
        $output->resources = $object->getResources();

        return $output;
    }
}
