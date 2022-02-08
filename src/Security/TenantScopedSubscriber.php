<?php

namespace App\Security;

use App\Entity\TenantScopedInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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

        if (!$entity instanceof TenantScopedInterface) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationException('Unknown User class passed');
        }

        $entity->setTenant($user->getActiveTenant());
    }
}
