<?php

namespace App\Repository;

use App\Entity\ScreenLayoutRegions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenLayoutRegions|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenLayoutRegions|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenLayoutRegions[]    findAll()
 * @method ScreenLayoutRegions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenLayoutRegionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenLayoutRegions::class);
    }

    public function findByUlid(Ulid $ulid): ?ScreenLayoutRegions
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.ulid = :ulid')
            ->setParameter('ulid', $ulid, 'ulid')
            ->getQuery()
            ->getResult();

        return $result ? reset($result) : null;
    }
}
