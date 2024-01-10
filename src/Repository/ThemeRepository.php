<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Theme|null find($id, $lockMode = null, $lockVersion = null)
 * @method Theme|null findOneBy(array $criteria, array $orderBy = null)
 * @method Theme[]    findAll()
 * @method Theme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThemeRepository extends ServiceEntityRepository
{
    private readonly EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getById(Ulid $themeId): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Theme::class, 's')
            ->where('s.id = :themeId')
            ->setParameter('themeId', $themeId, 'ulid');

        return $queryBuilder;
    }
}
