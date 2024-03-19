<?php

declare(strict_types=1);

namespace App\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Entity\Tenant;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Utils\ValidationUtils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        ManagerRegistry $registry,
        private readonly ValidationUtils $validationUtils,
    ) {
        parent::__construct($registry, PlaylistSlide::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getPlaylistSlideRelationsFromSlideId(Ulid $slideUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
        ->where('ps.slide = :slideId')
        ->setParameter('slideId', $slideUlid, 'ulid');

        return $queryBuilder;
    }

    public function getPlaylistSlideRelationsFromPlaylistId(Ulid $playlistUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
        ->where('ps.playlist = :playlistId')
        ->setParameter('playlistId', $playlistUlid, 'ulid')
        ->orderBy('ps.weight', 'ASC');

        return $queryBuilder;
    }

    public function getPlaylistsFromSlideId(Ulid $id): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
        ->where('ps.slide = :slideId')
        ->setParameter('slideId', $id, 'ulid');

        return $queryBuilder;
    }

    public function updatePlaylistSlideRelations(Ulid $playlistUlid, ArrayCollection $collection, Tenant $tenant): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $playlistUlid, 'tenant' => $tenant]);

        if (is_null($playlist)) {
            throw new InvalidArgumentException('Playlist not found');
        }

        $slideRepos = $this->entityManager->getRepository(Slide::class);

        try {
            $entities = $this->findBy(['playlist' => $playlistUlid]);
            $slideIdsToAdd = array_map(fn ($entry) => $entry->slide, $collection->toArray());

            $entitiesToRemove = array_reduce($entities, function (array $carry, PlaylistSlide $item) use ($slideIdsToAdd) {
                $slide = $item->getSlide();
                $id = $slide->getId();
                if (!in_array($id, $slideIdsToAdd)) {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            foreach ($entitiesToRemove as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($slideIdsToAdd as $slideIdToAdd) {
                $slide = $slideRepos->findOneBy(['id' => $slideIdToAdd, 'tenant' => $tenant]);

                if (is_null($slide)) {
                    throw new InvalidArgumentException('Slide not found');
                }

                $playlistSlideRelations = $this->findOneBy(['slide' => $slide, 'playlist' => $playlist]);

                if (is_null($playlistSlideRelations)) {
                    $ulid = $this->validationUtils->validateUlid($slide->getId());
                    $weight = $this->getWeight($ulid);

                    // Create new relation.
                    $ps = new PlaylistSlide();
                    $ps->setPlaylist($playlist)
                        ->setSlide($slide)
                        ->setWeight($weight);

                    $this->entityManager->persist($ps);
                }
            }
            $this->entityManager->flush();

            // Try and commit the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    public function updateSlidePlaylistRelations(Ulid $slideUlid, ArrayCollection $collection, Tenant $tenant): void
    {
        $slideRepos = $this->entityManager->getRepository(Slide::class);
        $slide = $slideRepos->findOneBy(['id' => $slideUlid, 'tenant' => $tenant]);

        if (is_null($slide)) {
            throw new InvalidArgumentException('Slide not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $entities = $this->findBy(['slide' => $slideUlid]);
            $playlistIdsToAdd = array_map(fn ($entry) => $entry->playlist, $collection->toArray());

            $entitiesToRemove = array_reduce($entities, function (array $carry, PlaylistSlide $item) use ($playlistIdsToAdd) {
                $playlist = $item->getPlaylist();
                $id = $playlist->getId();
                if (!in_array($id, $playlistIdsToAdd)) {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            foreach ($entitiesToRemove as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($playlistIdsToAdd as $playlistIdToAdd) {
                $playlist = $playlistRepos->findOneBy(['id' => $playlistIdToAdd, 'tenant' => $tenant]);

                if (is_null($playlist)) {
                    throw new InvalidArgumentException('Playlist not found');
                }

                $playlistSlideRelations = $this->findOneBy(['slide' => $slide, 'playlist' => $playlist]);

                if (is_null($playlistSlideRelations)) {
                    $ulid = $this->validationUtils->validateUlid($playlist->getId());
                    $weight = $this->getWeight($ulid);

                    // Create new relation.
                    $ps = new PlaylistSlide();
                    $ps->setPlaylist($playlist)
                    ->setSlide($slide)
                    ->setWeight($weight);

                    $this->entityManager->persist($ps);
                }
            }
            $this->entityManager->flush();

            // Try and commit the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    public function deleteRelations(Ulid $ulid, Ulid $slideUlid, Tenant $tenant): void
    {
        $playlistSlide = $this->findOneBy(['playlist' => $ulid, 'slide' => $slideUlid, 'tenant' => $tenant]);

        if (is_null($playlistSlide)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistSlide);
        $this->entityManager->flush();
    }

    private function getWeight(Ulid $ulid): int
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
            ->where('ps.playlist = :playlistId')
            ->setParameter('playlistId', $ulid, 'ulid')
            ->orderBy('ps.weight', 'DESC');

        // Get the current max weight in the relation between slide/playlist
        $playlistSlide = $queryBuilder->getQuery()->setMaxResults(1)->execute();
        if (0 === count($playlistSlide)) {
            return 0;
        } else {
            return $playlistSlide[0]->getWeight() + 1;
        }
    }
}
