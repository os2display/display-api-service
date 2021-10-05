<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaylistSlide::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getSlidePaginator(Ulid $playlistUid, int $page = 1, int $itemsPerPage = 10): Paginator
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

    public function getPlaylistSlidesBaseOnPlaylist(Ulid $playlistUid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
            ->where('ps.playlist = :playlistId')
            ->setParameter('playlistId', $playlistUid, 'ulid')
            ->orderBy('ps.weight', 'ASC');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function updateRelations(Ulid $playlistUlid, ArrayCollection $collection)
    {
        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $playlistUlid]);
        if (is_null($playlist)) {
            throw new InvalidArgumentException('Playlist not found');
        }

        $slideRepos = $this->entityManager->getRepository(Slide::class);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between slides and current playlist.
            $entities = $this->findBy(['playlist' => $playlist]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($collection as $entity) {
                $slide = $slideRepos->findOneBy(['id' => $entity->slide]);
                if (is_null($slide)) {
                    throw new InvalidArgumentException('Playlist not found');
                }

                // Create new relation.
                $ps = new PlaylistSlide();
                $ps->setPlaylist($playlist)
                    ->setSlide($slide)
                    ->setWeight($entity->weight);

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

    public function deleteRelations(Ulid $ulid, Ulid $slideUlid)
    {
        $playlistSlide = $this->findOneBy(['playlist' => $ulid, 'slide' => $slideUlid]);

        if (is_null($playlistSlide)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistSlide);
        $this->entityManager->flush();
    }
}
