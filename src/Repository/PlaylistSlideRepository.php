<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\Playlist;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

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

    public function getSlidePaginator(Ulid $playlistUid, int $page = 1, int $itemsPerPage = 10)
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Slide::class, 's')
            ->innerJoin('s.playlistSlides', 'ps', Join::WITH, 'ps.playlist = :playlistId')
            ->setParameter('playlistId', $playlistUid, 'ulid')
            ->orderBy('ps.weight', 'ASC');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function getPlaylistPaginator(Ulid $slideUlidObj, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('p')
            ->from(Playlist::class, 'p')
            ->innerJoin('p.playlistSlides', 'ps', Join::WITH, 'ps.playlist = :slideId')
            ->setParameter('slideId', $slideUlidObj, 'ulid')
            ->orderBy('ps.weight', 'ASC');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
