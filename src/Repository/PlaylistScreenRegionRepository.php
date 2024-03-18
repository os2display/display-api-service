<?php

declare(strict_types=1);

namespace App\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, PlaylistScreenRegion::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getPlaylistsByScreenRegion(Ulid $screenUlid, Ulid $regionUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('psr')
            ->where('psr.screen = :screen')
            ->setParameter('screen', $screenUlid, 'ulid')
            ->andWhere('psr.region = :region')
            ->setParameter('region', $regionUlid, 'ulid');

        return $queryBuilder;
    }

    /**
     * Create relation between a playlist in a given region on a given screen.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateRelations(Ulid $screenUlid, Ulid $regionUlid, ArrayCollection $collection, Tenant $tenant): void
    {
        $screenRepos = $this->getEntityManager()->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid, 'tenant' => $tenant]);

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
            $entities = $this->findBy(['screen' => $screen, 'region' => $region, 'tenant' => $tenant]);

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            $this->entityManager->flush();

            foreach ($collection as $entity) {
                $playlist = $playlistRepos->findOneBy(['id' => $entity->playlist, 'tenant' => $tenant]);

                if (is_null($playlist)) {
                    $playlist = $playlistRepos->findOneBy(['id' => $entity->playlist]);

                    if (!in_array($tenant, $playlist->getTenants()->toArray())) {
                        throw new InvalidArgumentException('Playlist not found');
                    }
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
    public function deleteRelations(Ulid $screenUlid, Ulid $regionUid, Ulid $playlistUlid, Tenant $tenant): void
    {
        $playlistScreenRegion = $this->findOneBy([
            'screen' => $screenUlid,
            'region' => $regionUid,
            'playlist' => $playlistUlid,
            'tenant' => $tenant,
        ]);

        if (is_null($playlistScreenRegion)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistScreenRegion);
        $this->entityManager->flush();
    }

    /**
     * Remove all relations a playlist has within a tenant.
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteRelationsPlaylistsTenant(Ulid $playlistUlid, string $tenantUlid): void
    {
        $playlistScreenRegion = $this->findOneBy([
            'playlist' => $playlistUlid,
            'tenant' => $tenantUlid,
        ]);

        if (is_null($playlistScreenRegion)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistScreenRegion);
        $this->entityManager->flush();
    }
}
