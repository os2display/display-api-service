<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\TenantScopedEntityInterface;
use App\Entity\Interfaces\TenantScopedUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class TenantExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhereCollection($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
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

        $tenantId = $tenant->getId();

        if (null === $tenantId) {
            throw new \Exception('Tenant id null');
        }

        if ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class) && $targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant OR :tenant MEMBER OF %s.tenants', $rootAlias, $rootAlias))
                ->setParameter('tenant', $tenantId->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
                ->setParameter('tenant', $tenantId->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
                ->setParameter('tenant', $tenantId->toBinary());
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

        $tenantId = $tenant->getId();

        if (null === $tenantId) {
            throw new \Exception('Tenant id null');
        }

        if ($targetEntity->getReflectionClass()->implementsInterface(TenantScopedEntityInterface::class)) {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
                ->setParameter('tenant', $tenantId->toBinary());
        } elseif ($targetEntity->getReflectionClass()->implementsInterface(MultiTenantInterface::class)) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
                ->setParameter('tenant', $tenantId->toBinary());
        }
    }
}
