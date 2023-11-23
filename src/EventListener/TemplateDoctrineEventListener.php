<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Template;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TemplateDoctrineEventListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function prePersist(Template $template, LifecycleEventArgs $event): void
    {
        $tenantRepository = $this->entityManager->getRepository(Tenant::class);
        $tenants = $tenantRepository->findAll();

        $template->setTenants($tenants);
    }
}
