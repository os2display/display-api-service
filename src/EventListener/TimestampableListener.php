<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::prePersist, priority: 10)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 10)]
class TimestampableListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof TimestampableInterface) {
            $entity->setCreatedAt();
            $entity->setModifiedAt();
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof TimestampableInterface) {
            $entity->setModifiedAt();
        }
    }
}
