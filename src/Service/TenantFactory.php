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
     * Get Tenants from array of tenant keys. Create new Tenants
     * for unknown keys.
     *
     * @param array $tenantKeys
     * @param string $createdBy
     *
     * @return Tenant[]
     *
     * @throws QueryException
     */
    public function setupTenants(array $tenantKeys, string $createdBy = self::class): array
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
