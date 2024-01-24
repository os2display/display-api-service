<?php

namespace App\Repository;

use App\Entity\Tenant\Interactive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interactive>
 *
 * @method Interactive|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interactive|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interactive[]    findAll()
 * @method Interactive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InteractiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interactive::class);
    }

//    /**
//     * @return Interactive[] Returns an array of Interactive objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Interactive
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
