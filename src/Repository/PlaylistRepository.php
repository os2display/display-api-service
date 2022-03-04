<?php

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
}
