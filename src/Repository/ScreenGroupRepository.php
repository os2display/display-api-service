<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\ScreenGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenGroup[]    findAll()
 * @method ScreenGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenGroup::class);
    }

    public function getScreenGroups(Ulid $screenUlid, int $page, int $itemsPerPage): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screens', 's', Join::WITH, 's.id = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
