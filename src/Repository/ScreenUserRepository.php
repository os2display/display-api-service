<?php

namespace App\Repository;

use App\Entity\Tenant\ScreenUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ScreenUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenUser[]    findAll()
 * @method ScreenUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenUser::class);
    }

    // /**
    //  * @return ScreenUser[] Returns an array of ScreenUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ScreenUser
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
