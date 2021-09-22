<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
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
        $screen = $screenRepos->findOneBy(['id' => $screenUlid]);

        $regionLayoutRepos = $this->getEntityManager()->getRepository(ScreenLayoutRegions::class);
        $region = $regionLayoutRepos->findOneBy(['id' => $regionUid]);

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

    /**
     * Create relation between a playlist in a given region on a given screen.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function linkPlaylistsByScreenRegion(Ulid $screenUlid, Ulid $regionUid, Ulid $playlistUlid): void
    {
        $screenRepos = $this->getEntityManager()->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $regionRepos = $this->getEntityManager()->getRepository(ScreenLayoutRegions::class);
        $region = $regionRepos->findOneBy(['id' => $regionUid]);
        if (is_null($region)) {
            throw new InvalidArgumentException('Region not found');
        }

        $playlistRepos = $this->getEntityManager()->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $playlistUlid]);
        if (is_null($playlist)) {
            throw new InvalidArgumentException('Playlist not found');
        }

        $playlistScreenRegion = new PlaylistScreenRegion();
        $playlistScreenRegion->setScreen($screen)
            ->setRegion($region)
            ->setPlaylist($playlist);

        $em = $this->getEntityManager();
        $em->persist($playlistScreenRegion);
        $em->flush();
    }

    /**
     * Remove relation between a playlist in a given region on a given screen.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unlinkPlaylistsByScreenRegion(Ulid $screenUlid, Ulid $regionUid, Ulid $playlistUlid): void
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.screen = :screen')
            ->setParameter('screen', $screenUlid, 'ulid')
            ->andwhere('p.region = :region')
            ->setParameter('region', $regionUid, 'ulid')
            ->andwhere('p.playlist = :playlist')
            ->setParameter('playlist', $playlistUlid, 'ulid');
        $result = $queryBuilder->getQuery()->execute();

        if (empty($result)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $playlistScreenRegion = reset($result);
        $em = $this->getEntityManager();
        $em->remove($playlistScreenRegion);
        $em->flush();
    }
}
