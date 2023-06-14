<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\Interfaces\TenantScopedUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class TenantExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $this->addWhereCollection($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        $this->addWhereItem($queryBuilder, $resourceClass);
    }

    private function addWhereCollection(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof TenantScopedUserInterface) {
            return;
        }
        $tenant = $user->getActiveTenant();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $targetEntity = $this->entityManager->getClassMetaData($resourceClass);
        if ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class) && $targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant OR :tenant MEMBER OF %s.tenants', $rootAlias, $rootAlias))
                ->setParameter('tenant', $tenant->getId()?->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
                ->setParameter('tenant', $tenant->getId()?->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
                ->setParameter('tenant', $tenant->getId()?->toBinary());
        }
    }

    private function addWhereItem(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof TenantScopedUserInterface) {
            return;
        }
        $tenant = $user->getActiveTenant();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $targetEntity = $this->entityManager->getClassMetaData($resourceClass);

        if ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
                ->setParameter('tenant', $tenant->getId()?->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
                ->setParameter('tenant', $tenant->getId()?->toBinary());
        }
    }
}
