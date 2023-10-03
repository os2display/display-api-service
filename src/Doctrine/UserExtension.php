<?php

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Exceptions\UserException;
use App\Utils\Roles;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class UserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /**
     * @throws UserException
     */
    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (User::class !== $resourceClass) {
            return;
        }

        $user = $this->security->getUser();

        if (null === $user) {
            throw new UserException('No user');
        }

        $roles = $user->getRoles();

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (in_array(Roles::ROLE_USER_ADMIN, $roles)) {
            // No extra needed here.
        } elseif (in_array(Roles::ROLE_EXTERNAL_USER_ADMIN, $roles)) {
            $queryBuilder->andWhere(sprintf('%s.userType = :userType', $rootAlias));
            $queryBuilder->setParameter('userType', UserTypeEnum::OIDC_EXTERNAL);
        } else {
            // A user should always have a userType, so this will result in zero results.
            $queryBuilder->andWhere(sprintf('%s.userType is NULL', $rootAlias));
        }
    }
}
