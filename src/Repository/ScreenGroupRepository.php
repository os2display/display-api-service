<?php

namespace App\Repository;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
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

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, ScreenGroup::class);

        $this->entityManager = $this->getEntityManager();
        $this->security = $security;
    }

    public function getScreenGroupsByScreenId(Ulid $screenUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screens', 's', Join::WITH, 's.id = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        return $queryBuilder;
    }

    public function updateRelations(Ulid $screenUlid, ArrayCollection $collection)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $screenRepos = $this->entityManager->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid, 'tenant' => $tenant]);

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
                $group = $screenGroupRepos->findOneBy(['id' => $screenGroupId, 'tenant' => $tenant]);
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

    public function deleteRelations(Ulid $screenUlid, Ulid $screenGroupUlid)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $screenRepos = $this->entityManager->getRepository(Screen::class);
        $screen = $screenRepos->findOneBy(['id' => $screenUlid, 'tenant' => $tenant]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $screenGroupRepos = $this->entityManager->getRepository(ScreenGroup::class);
        $screenGroup = $screenGroupRepos->findOneBy(['id' => $screenGroupUlid, 'tenant' => $tenant]);
        if (is_null($screenGroup)) {
            throw new InvalidArgumentException('Screen group not found');
        }

        if (!$screen->getScreenGroups()->contains($screenGroup)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $screen->removeScreenGroup($screenGroup);
        $this->entityManager->flush();
    }
}
