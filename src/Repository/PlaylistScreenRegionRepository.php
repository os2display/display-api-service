<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\PlaylistScreenRegion;
use App\Entity\Screen;
use App\Entity\ScreenLayoutRegions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method PlaylistScreenRegion|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlaylistScreenRegion|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlaylistScreenRegion[]    findAll()
 * @method PlaylistScreenRegion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaylistScreenRegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaylistScreenRegion::class);
    }

    public function getPlaylistsByScreenRegion(Ulid $screenUlid, Ulid $regionUid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $screenRepos = $this->getEntityManager()->getRepository(Screen::class);
        $screen = $screenRepos->findByUlid($screenUlid);

        $regionLayoutRepos = $this->getEntityManager()->getRepository(ScreenLayoutRegions::class);
        $region = $regionLayoutRepos->findByUlid($regionUid);

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('p')
            ->from('App\Entity\Playlist', 'p')
            ->leftJoin('App\Entity\PlaylistScreenRegion', 'plsr', Join::WITH, 'p.id = plsr.playlist')
            ->where('plsr.screen = :screen')
            ->setParameter('screen', $screen)
            ->andWhere('plsr.region = :region')
            ->setParameter('region', $region);

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
