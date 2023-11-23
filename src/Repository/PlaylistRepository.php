<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\Playlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getById(Ulid $playlistId): Querybuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(Playlist::class, 's')
            ->where('s.id = :playlistId')
            ->setParameter('playlistId', $playlistId, 'ulid');

        return $queryBuilder;
    }
}
