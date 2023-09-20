<?php

namespace App\Repository;

use App\Entity\Tenant\ExternalUserActivationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalUserActivationCode>
 *
 * @method ExternalUserActivationCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalUserActivationCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalUserActivationCode[]    findAll()
 * @method ExternalUserActivationCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalUserActivationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalUserActivationCode::class);
    }

    public function save(ExternalUserActivationCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExternalUserActivationCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
