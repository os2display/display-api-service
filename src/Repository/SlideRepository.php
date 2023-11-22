<?php

namespace App\Repository;

use App\Entity\Tenant\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Slide|null find($id, $lockMode = null, $lockVersion = null)
 * @method Slide|null findOneBy(array $criteria, array $orderBy = null)
 * @method Slide[]    findAll()
 * @method Slide[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slide::class);
    }

    public function save(Slide $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Slide $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getSlidesByMedia(Ulid $mediaUlid): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Slide::class, 's')
            ->innerJoin('s.media', 'm', Join::WITH, ' m.id = :mediaId')
            ->setParameter('mediaId', $mediaUlid, 'ulid');

        return $queryBuilder;
    }

    public function getSlidesByTheme(Ulid $themeUlid): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Slide::class, 's')
            ->innerJoin('s.theme', 't', Join::WITH, ' t.id = :themeId')
            ->setParameter('themeId', $themeUlid, 'ulid');

        return $queryBuilder;
    }

    public function getSlidesWithFeedData(): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('s')
            ->from(Slide::class, 's')
            ->where('s.feed IS NOT NULL');

        return $queryBuilder;
    }
}
