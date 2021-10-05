<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Screen;
use App\Entity\ScreenGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenGroup[]    findAll()
 * @method ScreenGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenGroupRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenGroup::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getScreenGroups(Ulid $screenUlid, int $page, int $itemsPerPage): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screens', 's', Join::WITH, 's.id = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function updateRelations(Ulid $screenUlid, ArrayCollection $collection)
    {
        $screenRepos = $this->entityManager->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $screenGroupRepos = $this->entityManager->getRepository(ScreenGroup::class);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between slides and current screen.
            $screen->removeAllScreenGroup();
            $this->entityManager->flush();

            foreach ($collection as $screenGroupId) {
                $group = $screenGroupRepos->findOneBy(['id' => $screenGroupId]);
                if (is_null($group)) {
                    throw new InvalidArgumentException('Screen group not found');
                }

                $screen->addScreenGroup($group);
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
}
