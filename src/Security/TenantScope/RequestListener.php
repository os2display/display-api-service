<?php

namespace App\Security\TenantScope;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

/**
 * RequestListener Class.
 *
 * Symfony kernel request subscriber to activate the 'tenant' doctrine filter with
 * the users active tenant.
 *
 * @see App\Security\TenantScope\DoctrineFilter
 */
class RequestListener implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private Security $security, private RequestStack $requestStack)
    {
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['enableTenantFilter', EventPriorities::PRE_READ],
        ];
    }

    /**
     * Enable the 'tenant' doctrine filter with the users active tenant.
     *
     * @return void
     */
    public function enableTenantFilter(): void
    {
        $user = $this->security->getUser();
        // Tenantkey is in the request, meaning there is a request for shared entities (playlist)
        $sharedWithTenants = $this->requestStack->getCurrentRequest()->query->get('tenants_tenantKey');

        if ($user instanceof User) {
            $filter = $this->entityManager->getFilters()->enable('tenant_filter');
            $filter->setParameter('tenant_id', $user->getActiveTenant()->getId()->toBinary());
            $filter->setParameter('shared', $sharedWithTenants);
        }
    }
}
