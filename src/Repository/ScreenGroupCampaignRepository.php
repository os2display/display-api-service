<?php

namespace App\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
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

    public function __construct(
        ManagerRegistry $registry,
        private Security $security
    ) {
        parent::__construct($registry, ScreenGroupCampaign::class);

        $this->entityManager = $this->getEntityManager();
    }

    public function getScreenGroupsFromCampaignId(Ulid $campaignUlid): Querybuilder
    {
        $queryBuilder = $this->createQueryBuilder('ps');
        $queryBuilder->select('ps')
        ->where('ps.campaign = :campaignId')
        ->setParameter('campaignId', $campaignUlid, 'ulid');

        return $queryBuilder;
    }

    public function getCampaignsFromScreenGroupId(Ulid $screenGroupUlid, int $page = 1, int $itemsPerPage = 10): Querybuilder
    {
        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.screenGroup = :screenGroupId')
            ->setParameter('screenGroupId', $screenGroupUlid, 'ulid');

        return $queryBuilder;
    }

    public function updateRelations(Ulid $campaignUlid, ArrayCollection $collection)
    {
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $screenGroupsRepos = $this->entityManager->getRepository(ScreenGroup::class);
        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $campaign = $playlistRepos->findOneBy(['id' => $campaignUlid, 'tenant' => $tenant]);
        if (is_null($campaign)) {
            throw new InvalidArgumentException('Campaign not found');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between playlists/campaigns and current screen group.
            $entities = $this->findBy(['campaign' => $campaign, 'tenant' => $tenant]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            if (0 == count($collection)) {
                $screenGroupCampaign = $this->findOneBy(['campaign' => $campaignUlid, 'tenant' => $tenant]);
                if ($screenGroupCampaign) {
                    $this->entityManager->remove($screenGroupCampaign);
                    $this->entityManager->flush();
                }
            }

            foreach ($collection as $entity) {
                $screenGroup = $screenGroupsRepos->findOneBy(['id' => $entity->screengroup, 'tenant' => $tenant]);
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
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $screenGroupCampaign = $this->findOneBy(['screenGroup' => $ulid, 'campaign' => $campaignUlid, 'tenant' => $tenant]);

        if (is_null($screenGroupCampaign)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($screenGroupCampaign);
        $this->entityManager->flush();
    }
}
