<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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
    }

    public function getScreensByScreenGroupId(Ulid $screenGroupUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screenGroups', 's', Join::WITH, 's.id = :screenGroupId')
            ->setParameter('screenGroupId', $screenGroupUlid, 'ulid');

        return $queryBuilder;
    }
}
