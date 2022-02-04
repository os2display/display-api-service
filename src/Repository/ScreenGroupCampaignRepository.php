<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\ScreenGroup;
use App\Entity\ScreenGroupCampaign;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenGroupCampaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenGroupCampaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenGroupCampaign[]    findAll()
 * @method ScreenGroupCampaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenGroupCampaignRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenGroupCampaign::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getCampaignPaginator(Ulid $campaignUlid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('s')
            ->from(ScreenGroup::class, 's')
            ->innerJoin('s.screenGroupCampaigns', 'ps', Join::WITH, 'ps.campaign = :campaignId')
            ->setParameter('campaignId', $campaignUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function getScreenGroupCampaignsBasedOnScreenGroup(Ulid $screenGroupUlid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.screenGroup = :screenGroupId')
            ->setParameter('screenGroupId', $screenGroupUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function updateRelations(Ulid $campaignUlid, ArrayCollection $collection)
    {
        $screenGroupsRepos = $this->entityManager->getRepository(ScreenGroup::class);
        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $campaign = $playlistRepos->findOneBy(['id' => $campaignUlid]);
        if (is_null($campaign)) {
            throw new InvalidArgumentException('Campaign not found');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between playlists/campaigns and current screen group.
            $entities = $this->findBy(['campaign' => $campaign]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($collection as $entity) {
                $screenGroup = $screenGroupsRepos->findOneBy(['id' => $entity->screengroup]);
                if (is_null($screenGroup)) {
                    throw new InvalidArgumentException('Screen group not found');
                }

                // Create new relation.
                $ps = new ScreenGroupCampaign();
                $ps->setScreenGroup($screenGroup)->setCampaign($campaign);

                $this->entityManager->persist($ps);
                $this->entityManager->flush();
            }

            // Try and commit the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }
    }

    public function deleteRelations(Ulid $ulid, Ulid $campaignUlid)
    {
        $screenGroupCampaign = $this->findOneBy(['screenGroup' => $ulid, 'campaign' => $campaignUlid]);

        if (is_null($screenGroupCampaign)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($screenGroupCampaign);
        $this->entityManager->flush();
    }
}
