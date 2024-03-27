<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\InteractiveSlide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InteractiveSlide>
 *
 * @method InteractiveSlide|null find($id, $lockMode = null, $lockVersion = null)
 * @method InteractiveSlide|null findOneBy(array $criteria, array $orderBy = null)
 * @method InteractiveSlide[]    findAll()
 * @method InteractiveSlide[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InteractiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InteractiveSlide::class);
    }
}
