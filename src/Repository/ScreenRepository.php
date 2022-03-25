<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\Tenant\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

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
        $this->entityManager = $this->getEntityManager();
    }

    public function getScreens(Ulid $screenGroupUlid, int $page, int $itemsPerPage): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screenGroups', 's', Join::WITH, 's.id = :screenGroupId')
            ->setParameter('screenGroupId', $screenGroupUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
