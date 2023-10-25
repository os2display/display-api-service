<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Dto\ScreenGroupInput;
use App\Entity\Tenant\ScreenGroup;

class ScreenGroupProcessor extends AbstractProcessor
{
    /**
     * @return T
     */
    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): ScreenGroup
    {
        $screenGroup = new ScreenGroup();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screenGroup = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ScreenGroupInput $object */
        empty($object->title) ?: $screenGroup->setTitle($object->title);
        empty($object->description) ?: $screenGroup->setDescription($object->description);
        empty($object->createdBy) ?: $screenGroup->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screenGroup->setModifiedBy($object->modifiedBy);

        return $screenGroup;
    }
}
