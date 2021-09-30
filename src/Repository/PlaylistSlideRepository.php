<?php

namespace App\Repository;

use App\Entity\PlaylistSlide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PlaylistSlide|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlaylistSlide|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlaylistSlide[]    findAll()
 * @method PlaylistSlide[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaylistSlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaylistSlide::class);
    }

    // /**
    //  * @return PlaylistSlide[] Returns an array of PlaylistSlide objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PlaylistSlide
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
