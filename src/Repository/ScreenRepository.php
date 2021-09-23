<?php

namespace App\Repository;

use App\Entity\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Screen|null find($id, $lockMode = null, $lockVersion = null)
 * @method Screen|null findOneBy(array $criteria, array $orderBy = null)
 * @method Screen[]    findAll()
 * @method Screen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Screen::class);
    }

    // /**
    //  * @return ScreenLayout[] Returns an array of ScreenLayout objects
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
    public function findOneBySomeField($value): ?ScreenLayout
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
