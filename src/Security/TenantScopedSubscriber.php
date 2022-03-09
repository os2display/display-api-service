<?php

namespace App\Security;

use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class TenantScopedSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->setTenant($args);
    }

    private function setTenant(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TenantScopedEntityInterface) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $entity->setTenant($user->getActiveTenant());
        }
    }
}
