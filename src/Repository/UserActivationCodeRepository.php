<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\UserActivationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActivationCode>
 *
 * @method UserActivationCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserActivationCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserActivationCode[]    findAll()
 * @method UserActivationCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserActivationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivationCode::class);
    }

    public function save(UserActivationCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserActivationCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
