<?php

namespace App\EventSubscriber;

use App\Entity\Interfaces\BlameableInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class BlameableSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var User $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setCreatedBy($user->getEmail());
                $entity->setModifiedBy($user->getEmail());
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var User $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setModifiedBy($user->getEmail());
            }
        }
    }
}
