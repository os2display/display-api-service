<?php

namespace App\Repository;

use App\Entity\Tenant\FeedSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedSource|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedSource[]    findAll()
 * @method FeedSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedSource::class);
    }
}
