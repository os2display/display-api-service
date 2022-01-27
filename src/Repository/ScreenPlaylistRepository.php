<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\Screen;
use App\Entity\ScreenPlaylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenPlaylist|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenPlaylist|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenPlaylist[]    findAll()
 * @method ScreenPlaylist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenPlaylistRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenPlaylist::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getPlaylistPaginator(Ulid $screenUid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Playlist::class, 's')
            ->innerJoin('s.screenPlaylists', 'ps', Join::WITH, 'ps.screen = :screenId')
            ->setParameter('screenId', $screenUid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);
        $sf = new Paginator($doctrinePaginator);

        return new Paginator($doctrinePaginator);
    }

    public function getScreenPlaylistsBasedOnScreen(Ulid $screenUlid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.screen = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function updateRelations(Ulid $screenUlid, ArrayCollection $collection)
    {
        $screensRepos = $this->entityManager->getRepository(Screen::class);
        $screen = $screensRepos->findOneBy(['id' => $screenUlid]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between playlists and current screen.
            $entities = $this->findBy(['screen' => $screen]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($collection as $entity) {
                $playlist = $playlistRepos->findOneBy(['id' => $entity->playlist]);
                if (is_null($playlist)) {
                    throw new InvalidArgumentException('Playlist not found');
                }

                // Create new relation.
                $ps = new ScreenPlaylist();
                $ps->setScreen($screen)
                    ->setPlaylist($playlist);

                $this->entityManager->persist($ps);
                $this->entityManager->flush();
            }

            // Try and commit the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    public function deleteRelations(Ulid $ulid, Ulid $playlistUlid)
    {
        $screenPlaylist = $this->findOneBy(['screen' => $ulid, 'playlist' => $playlistUlid]);

        if (is_null($screenPlaylist)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($screenPlaylist);
        $this->entityManager->flush();
    }
}
