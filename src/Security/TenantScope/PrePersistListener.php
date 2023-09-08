<?php

namespace App\Security\TenantScope;


use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events as VichEvents;


/**
 * PrePersistListener Class.
 *
 * Doctrine event listener to set tenant of all new
 *  entities from the Users active tenant.
 *
 * Previously, with Symfony's EventSubscriberInterface,
 *  MediaUploadTenantDirectoryNamer was called before the
 *  Doctrine prePersist event. With AsEventListener, this is not the case,
 *  therefore the addition of VichUploaderBundle pre upload listener.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsEventListener(event: VichEvents::PRE_UPLOAD, method: 'vichPreUpload')]
class PrePersistListener
{
    public function __construct(
        private Security $security
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->setTenant($args->getObject());
    }

    public function vichPreUpload(Event $event): void
    {
        $this->setTenant($event->getObject());
    }

    /**
     * Set entity tenant from users active tenant.
     *
     * @param Object $object
     *
     * @return void
     */
    private function setTenant(Object $object): void
    {
        if (!$object instanceof TenantScopedEntityInterface) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $object->setTenant($user->getActiveTenant());
        }
    }
}
