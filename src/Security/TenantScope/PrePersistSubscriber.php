<?php

namespace App\Security\TenantScope;

use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

/**
 * PrePersistSubscriber Class.
 *
 * Doctrine lifecycle event subscriber to set tenant of all new
 * entities from the Users active tenant.
 */
class PrePersistSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    /** {@inheritDoc} */
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

    /**
     * Set entity tenant from users active tenant.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
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
