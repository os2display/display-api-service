<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\ORM\Query\ResultSetMapping;

trait MultiTenantRepositoryTrait
{
    /**
     * Add 'Tenant' relation to all repository entities.
     *
     * This uses native sql insert directly into the relations table. This is done to avoid
     * having to load all entities into memory to build the relations.
     *
     * @return void
     */
    public function addTenantToAll(Tenant $tenant): void
    {
        $rsm = new ResultSetMapping();

        $meta = $this->getClassMetadata();
        $tableName = $meta->table['name'];

        $sql = sprintf('INSERT INTO %s_tenant (%s_id, tenant_id) SELECT id, ? FROM `%s`;', $tableName, $tableName, $tableName);

        $sqlQuery = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $sqlQuery->setParameter(1, $tenant->getId()->toBinary());

        $sqlQuery->execute();
    }
}
