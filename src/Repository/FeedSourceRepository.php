<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method FeedSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedSource|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedSource[]    findAll()
 * @method FeedSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedSourceRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($registry, FeedSource::class);
    }

    public function getFeedSourceSlideRelationsFromFeedSourceId(Ulid $feedSourceUlid): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->select('s', 'f', 'fs')
            ->from(Slide::class, 's')
            ->leftJoin('s.feed', 'f')
            ->leftJoin('f.feedSource', 'fs')
            ->where('fs.id = :feedSourceId')
            ->setParameter('feedSourceId', $feedSourceUlid, 'ulid');

        return $queryBuilder;
    }
}
