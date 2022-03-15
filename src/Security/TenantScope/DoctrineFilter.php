<?php

namespace App\Security\TenantScope;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * DoctrineFilter Class.
 *
 * This filter adds a 'tenant' filter to all queries to ensure that
 * only content from the users active tenant is shown.
 *
 * @see App\Security\TenantScope\DoctrineFilter
 */
class DoctrineFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getReflectionClass()->implementsInterface('App\Entity\Interfaces\TenantScopedEntityInterface')) {
            return sprintf('%s.tenant_id = %s', $targetTableAlias, $this->getParameter('tenant_id'));
        } elseif ($targetEntity->getReflectionClass()->implementsInterface('App\Entity\Interfaces\MultiTenantInterface')) {
            // @TODO Add filter to limit access -awaiting AR-544 'Shared Playlists'
        }
        // @TODO add playlist shared code: if a user requests playlists shared with him or her, the filter should somehow expand to other tenants
        return '';
    }
}
