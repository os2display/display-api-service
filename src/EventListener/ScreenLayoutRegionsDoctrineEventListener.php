<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ScreenLayoutRegionsDoctrineEventListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function prePersist(ScreenLayoutRegions $screenLayoutRegions, LifecycleEventArgs $event): void
    {
        $tenantRepository = $this->entityManager->getRepository(Tenant::class);
        $tenants = $tenantRepository->findAll();

        $screenLayoutRegions->setTenants($tenants);
    }
}
