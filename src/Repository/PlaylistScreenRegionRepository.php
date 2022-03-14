<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaylistScreenRegion::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getPlaylistsByScreenRegion(Ulid $screenUlid, Ulid $regionUlid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('psr')
            ->where('psr.screen = :screen')
            ->setParameter('screen', $screenUlid, 'ulid')
            ->andWhere('psr.region = :region')
            ->setParameter('region', $regionUlid, 'ulid');

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
    public function updateRelations(Ulid $screenUlid, Ulid $regionUlid, ArrayCollection $collection): void
    {
        $screenRepos = $this->getEntityManager()->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $regionRepos = $this->getEntityManager()->getRepository(ScreenLayoutRegions::class);
        $region = $regionRepos->findOneBy(['id' => $regionUlid]);
        if (is_null($region)) {
            throw new InvalidArgumentException('Region not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between slides and current playlist.
            $entities = $this->findBy(['screen' => $screen, 'region' => $region]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }
            $this->entityManager->flush();

            foreach ($collection as $entity) {
                $playlist = $playlistRepos->findOneBy(['id' => $entity->playlist]);
                if (is_null($playlist)) {
                    throw new InvalidArgumentException('Playlist not found');
                }

                // Create new relation.
                $psr = new PlaylistScreenRegion();
                $psr->setPlaylist($playlist)
                    ->setScreen($screen)
                    ->setRegion($region)
                    ->setWeight($entity->weight);

                $this->entityManager->persist($psr);
            }

            // Try and commit the transaction
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Remove relation between a playlist in a given region on a given screen.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteRelations(Ulid $screenUlid, Ulid $regionUid, Ulid $playlistUlid): void
    {
        $playlistScreenRegion = $this->findOneBy([
            'screen' => $screenUlid,
            'region' => $regionUid,
            'playlist' => $playlistUlid,
        ]);

        if (is_null($playlistScreenRegion)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistScreenRegion);
        $this->entityManager->flush();
    }
}
