<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Template|null find($id, $lockMode = null, $lockVersion = null)
 * @method Template|null findOneBy(array $criteria, array $orderBy = null)
 * @method Template[]    findAll()
 * @method Template[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateRepository extends ServiceEntityRepository implements MultiTenantRepositoryInterface
{
    use MultiTenantRepositoryTrait;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Template::class);
    }
}
