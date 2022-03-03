<?php

namespace App\Service;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;

class TenantFactory
{
    public function __construct(private EntityManagerInterface $entityManager, private TenantRepository $tenantRepository)
    {
    }

    /**
     * Get Tenants from array og tenent keys. Create new Tenants
     * for unknown keys.
     *
     * @param array $tenantKeys
     *
     * @return array
     *
     * @throws QueryException
     */
    public function getTenants(array $tenantKeys): array
    {
        $tenants = $this->tenantRepository->findByKeys($tenantKeys);

        foreach ($tenantKeys as $tenantKey) {
            if (!array_key_exists($tenantKey, $tenants)) {
                $tenant = new Tenant();
                $tenant->setTenantKey($tenantKey);
                $tenants[$tenantKey] = $tenant;

                $this->entityManager->persist($tenant);
            }
        }

        $this->entityManager->flush();

        return $tenants;
    }
}
