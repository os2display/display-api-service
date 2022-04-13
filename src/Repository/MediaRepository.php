<?php

namespace App\Repository;

use App\Entity\Tenant\Media;
use App\Entity\Tenant\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function getById(Ulid $mediaId): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Media::class, 's')
            ->where('s.id = :mediaId')
            ->setParameter('mediaId', $mediaId, 'ulid');

        return $queryBuilder;
    }

    public function getPaginator(Ulid $slideUlid): Querybuilder
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Slide::class, 's')
            ->innerJoin('s.media', 'm', Join::WITH, ' m.id = :slideId')
            ->setParameter('slideId', $slideUlid, 'ulid');

        return $queryBuilder;
    }
}
