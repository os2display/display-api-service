<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\PlaylistSlide;
use App\Entity\Screen;
use App\Entity\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    private EntityManagerInterface $entityManager;

    public const LINK = 'link';
    public const UNLINK = 'unlink';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getScreenPaginator(Ulid $playlistUid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Screen::class, 's')
            ->innerJoin('s.playlists', 'p', Join::WITH, ' p.id = :playlistId')
            ->setParameter('playlistId', $playlistUid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    /**
     * Slide operations (link/unlink slides on a given playlist).
     *
     * @param ulid $ulid
     *   Playlist Ulid for the playlist to manipulate
     * @param Ulid $slideUlid
     *   Screen Ulid to link/unlink to the playlist
     * @param string $op
     *   The operation to perform (use the PlaylistRepository:: constants)
     * @param int $weight
     *   The slides on the playlist is weighted
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function slideOperation(Ulid $ulid, Ulid $slideUlid, int $weight = 0, string $op = PlaylistRepository::UNLINK): void
    {
        $slideRepos = $this->entityManager->getRepository(Slide::class);
        $slide = $slideRepos->findOneBy(['id' => $slideUlid]);
        if (is_null($slide)) {
            throw new InvalidArgumentException('Slide not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $ulid]);
        if (is_null($playlist)) {
            throw new InvalidArgumentException('Playlist not found');
        }

        switch ($op) {
            case self::LINK:
                // Check if this an update or a new link.
                $playlistSlideRepos = $this->entityManager->getRepository(PlaylistSlide::class);
                $playlistSlide = $playlistSlideRepos->findOneBy([
                    'playlist' => $ulid,
                    'slide' => $slideUlid,
                ]);

                if (is_null($playlistSlide)) {
                    $playlistSlide = new PlaylistSlide();
                }
                $playlistSlide->setSlide($slide)
                    ->setPlaylist($playlist)
                    ->setWeight($weight);
                $this->_em->persist($playlistSlide);
                $playlist->addPlaylistSlide($playlistSlide);
                break;

            case self::UNLINK:
                $playlistSlideRepos = $this->entityManager->getRepository(PlaylistSlide::class);
                $playlistSlide = $playlistSlideRepos->findOneBy([
                    'playlist' => $ulid,
                    'slide' => $slideUlid,
                ]);

                if (is_null($playlistSlide)) {
                    throw new InvalidArgumentException('Relation not found');
                }
                $this->entityManager->remove($playlistSlide);
                $this->entityManager->flush();
                break;
        }

        $this->getEntityManager()->flush();
    }
}
