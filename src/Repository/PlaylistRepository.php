<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\Playlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
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

    public function getCampaigns(int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('ps')
            ->from(Playlist::class, 'ps')
            ->where('ps.isCampaign = TRUE');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
