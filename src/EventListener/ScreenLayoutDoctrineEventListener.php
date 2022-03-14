<?php

namespace App\EventListener;

use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ScreenLayoutDoctrineEventListener
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function prePersist(\App\Entity\ScreenLayout $screenLayout, LifecycleEventArgs $event): void
    {
        $tenantRepository = $this->entityManager->getRepository(Tenant::class);
        $tenants = $tenantRepository->findAll();
    }
}
