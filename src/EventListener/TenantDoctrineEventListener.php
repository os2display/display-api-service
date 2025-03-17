<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Tenant;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TenantDoctrineEventListener
{
    public function __construct(
        private readonly iterable $repositories,
    ) {}

    public function postPersist(Tenant $tenant, LifecycleEventArgs $event): void
    {
        foreach ($this->repositories as $repository) {
            $repository->addTenantToAll($tenant);
        }
    }
}
