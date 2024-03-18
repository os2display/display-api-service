<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserRoleTenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getUsersByTenantQueryBuilder(Ulid $tenantUlid): QueryBuilder
    {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('utr')
            ->from(UserRoleTenant::class, 'utr')
            ->andWhere('utr.user = u')
            ->andWhere('utr.tenant = :tenant');

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->setParameter('tenant', $tenantUlid, 'ulid')
            ->andWhere($queryBuilder->expr()->exists($subQuery));

        return $queryBuilder;
    }

    public function getUsersByIdAndTenantQueryBuilder(Ulid $ulid, Ulid $tenantUlid): QueryBuilder
    {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('utr')
            ->from(UserRoleTenant::class, 'utr')
            ->andWhere('utr.user = u')
            ->andWhere('utr.tenant = :tenant');

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :uid')
            ->setParameter('uid', $ulid, 'ulid')
            ->setParameter('tenant', $tenantUlid, 'ulid')
            ->andWhere($queryBuilder->expr()->exists($subQuery));

        return $queryBuilder;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getUserByIdAndTenant(Ulid $ulid, Ulid $tenant): ?User
    {
        // TODO: Use this as a subquery to main query.
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('utr')
            ->from(UserRoleTenant::class, 'utr')
            ->andWhere('utr.user = :user')
            ->setParameter('user', $ulid, 'ulid')
            ->andWhere('utr.tenant = :tenant')
            ->setParameter('tenant', $tenant, 'ulid');

        $userInTenant = $subQuery->getQuery()->getOneOrNullResult();

        if (null == $userInTenant) {
            return null;
        }

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :user')
            ->setParameter('user', $ulid, 'ulid');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\User u
                WHERE u.email = :query'
        )
            ->setParameter('query', $identifier)
            ->getOneOrNullResult();
    }

    public function getById(Ulid $ulid): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :user')
            ->setParameter('user', $ulid, 'ulid');

        return $queryBuilder;
    }
}
