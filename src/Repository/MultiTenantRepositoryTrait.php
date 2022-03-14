<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\ORM\Query\ResultSetMapping;

trait MultiTenantRepositoryTrait
{
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
