<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ScreenGroupInput;
use App\Entity\Tenant\ScreenGroup;
use Doctrine\ORM\EntityManagerInterface;

class ScreenGroupProcessor extends AbstractProcessor
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        ScreenGroupProvider $provider
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    /**
     * @return T
     */
    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): ScreenGroup
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $screenGroup = $this->loadPrevious(new ScreenGroup(), $context);

        /* @var ScreenGroupInput $object */
        empty($object->title) ?: $screenGroup->setTitle($object->title);
        empty($object->description) ?: $screenGroup->setDescription($object->description);
        empty($object->createdBy) ?: $screenGroup->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screenGroup->setModifiedBy($object->modifiedBy);

        return $screenGroup;
    }
}
