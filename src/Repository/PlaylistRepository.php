<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Playlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Playlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Playlist[]    findAll()
 * @method Playlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaylistRepository extends ServiceEntityRepository
{
    public const LINK = 'link';
    public const UNLINK = 'unlink';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);
    }

    public function getPaginator(string $entityType, Ulid $playlistUid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from($entityType, 's')
            ->innerJoin('s.playlists', 'p', Join::WITH, ' p.id = :playlistId')
            ->setParameter('playlistId', $playlistUid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function slideOperation(Ulid $ulid, Ulid $slideUlid, string $op = 'link'): void
    {
        $slideRepos = $this->getEntityManager()->getRepository(Slide::class);
        $slide = $slideRepos->findOneBy(['id' => $slideUlid]);
        if (is_null($slide)) {
            throw new InvalidArgumentException('Slide not found');
        }

        $playlistRepos = $this->getEntityManager()->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $ulid]);
        if (is_null($playlist)) {
            throw new InvalidArgumentException('Playlist not found');
        }

        switch ($op) {
            case self::LINK:
                $playlist->addSlide($slide);
                break;

            case self::UNLINK:
                $playlist->removeSlide($slide);
                break;
        }

        $this->getEntityManager()->flush();
    }
}
