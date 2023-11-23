<?php

declare(strict_types=1);

namespace App\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Utils\ValidationUtils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
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

    public function __construct(
        ManagerRegistry $registry,
        private ValidationUtils $validationUtils,
        private Security $security
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

    public function updateRelations(Ulid $playlistUlid, ArrayCollection $collection)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $playlist = $playlistRepos->findOneBy(['id' => $playlistUlid, 'tenant' => $tenant]);
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
                $slide = $slideRepos->findOneBy(['id' => $entity->slide, 'tenant' => $tenant]);
                if (is_null($slide)) {
                    throw new InvalidArgumentException('Slide not found');
                }

                // Create new relation.
                $ps = new PlaylistSlide();
                $ps->setPlaylist($playlist)
                    ->setSlide($slide)
                    ->setWeight($entity->weight);

                $this->entityManager->persist($ps);
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

    private function getWeight($ulid)
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
        ->where('ps.playlist = :playlistId')
        ->setParameter('playlistId', $ulid, 'ulid')
        ->orderBy('ps.weight', 'DESC');

        // Get the current max weight in the relation between slide/playlist
        $playlistSlide = $queryBuilder->getQuery()->setMaxResults(1)->execute();
        if (0 == count($playlistSlide)) {
            return 0;
        } else {
            return $playlistSlide[0]->getWeight() + 1;
        }
    }

    public function updateSlidePlaylistRelations(Ulid $slideUlid, ArrayCollection $collection)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $slideRepos = $this->entityManager->getRepository(Slide::class);
        $slide = $slideRepos->findOneBy(['id' => $slideUlid, 'tenant' => $tenant]);
        if (is_null($slide)) {
            throw new InvalidArgumentException('Slide not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $this->entityManager->getConnection()->beginTransaction();

        try {
            if ($collection->isEmpty()) {
                $entities = $this->findBy(['slide' => $slideUlid]);
                foreach ($entities as $entity) {
                    $this->entityManager->remove($entity);
                    $this->entityManager->flush();
                }
            }

            foreach ($collection as $entity) {
                $playlist = $playlistRepos->findOneBy(['id' => $entity->playlist, 'tenant' => $tenant]);

                if (is_null($playlist)) {
                    throw new InvalidArgumentException('Playlist not found');
                }

                $playlistSlideRelations = $this->findOneBy(['slide' => $slide, 'playlist' => $playlist]);
                $ulid = $this->validationUtils->validateUlid($playlist->getId());

                if (is_null($playlistSlideRelations)) {
                    $weight = $this->getWeight($ulid);

                    // Create new relation.
                    $ps = new PlaylistSlide();
                    $ps->setPlaylist($playlist)
                    ->setSlide($slide)
                    ->setWeight($weight);

                    $this->entityManager->persist($ps);
                    $this->entityManager->flush();
                }
            }
            $this->entityManager->getConnection()->commit();
            // Try and commit the transaction
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    public function deleteRelations(Ulid $ulid, Ulid $slideUlid)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $playlistSlide = $this->findOneBy(['playlist' => $ulid, 'slide' => $slideUlid]);

        if (is_null($playlistSlide)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($playlistSlide);
        $this->entityManager->flush();
    }
}
