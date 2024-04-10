<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\BlameableInterface;
use App\Entity\Interfaces\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class BlameableListener
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var UserInterface $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setCreatedBy($user->getBlamableIdentifier());
                $entity->setModifiedBy($user->getBlamableIdentifier());
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var UserInterface $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setModifiedBy($user->getBlamableIdentifier());
            }
        }
    }
}
