<?php

namespace App\Repository;

use App\Entity\FeedSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedSource|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedSource[]    findAll()
 * @method FeedSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedSource::class);
    }

    // /**
    //  * @return FeedSource[] Returns an array of FeedSource objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FeedSource
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
