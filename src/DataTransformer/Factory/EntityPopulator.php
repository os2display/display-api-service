<?php

namespace App\DataTransformer;

use App\Dto\InputInterface;
use App\Entity\EntitySharedInterface;
use App\Utils\ValidationUtils;

class EntityPopulator
{
    public function __construct(
        private ValidationUtils $utils
    ) {
    }

    public function populate(EntitySharedInterface $entity, InputInterface $data): void
    {
        empty($data->getTitle()) ?: $entity->setTitle($data->getTitle());
        empty($data->getDescription()) ?: $entity->setDescription($data->getDescription());

        empty($data->getCreatedBy()) ?: $entity->setCreatedBy($data->getCreatedBy());
        empty($data->getModifiedBy()) ?: $entity->setModifiedBy($data->getModifiedBy());

        empty($data->published['from']) ?: $entity->setPublishedFrom($data->published['from']);
        empty($data->published['to']) ?: $entity->setPublishedTo($data->published['to']);
    }
}