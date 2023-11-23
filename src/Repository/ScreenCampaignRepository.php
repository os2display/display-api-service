<?php

declare(strict_types=1);

namespace App\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * @method ScreenCampaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScreenCampaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScreenCampaign[]    findAll()
 * @method ScreenCampaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScreenCampaignRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, ScreenCampaign::class);

        $this->entityManager = $this->getEntityManager();
        $this->security = $security;
    }

    public function getScreensBasedOnCampaign(Ulid $campaignUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.campaign = :campaignId')
            ->setParameter('campaignId', $campaignUlid, 'ulid');

        return $queryBuilder;
    }

    public function getScreenCampaignsBasedOnScreen(Ulid $screenUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.screen = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        return $queryBuilder;
    }

    public function updateRelations(Ulid $campaignUlid, ArrayCollection $collection)
    {
        $screensRepos = $this->entityManager->getRepository(Screen::class);
        $playlistRepos = $this->entityManager->getRepository(Playlist::class);
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();

        $campaign = $playlistRepos->findOneBy(['id' => $campaignUlid, 'tenant' => $tenant]);
        if (is_null($campaign)) {
            throw new InvalidArgumentException('Campaign not found');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between playlists/campaigns and current screen.
            $entities = $this->findBy(['campaign' => $campaign]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            if (0 == count($collection)) {
                $screenCampaign = $this->findOneBy(['campaign' => $campaignUlid, 'tenant' => $tenant]);
                if ($screenCampaign) {
                    $this->entityManager->remove($screenCampaign);
                    $this->entityManager->flush();
                }
            }

            foreach ($collection as $entity) {
                $screen = $screensRepos->findOneBy(['id' => $entity->screen, 'tenant' => $tenant]);
                if (is_null($screen)) {
                    throw new InvalidArgumentException('Screen not found');
                }

                // Create new relation.
                $ps = new ScreenCampaign();
                $ps->setScreen($screen)->setCampaign($campaign);

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
        $screenCampaign = $this->findOneBy(['screen' => $ulid, 'campaign' => $campaignUlid, 'tenant' => $tenant]);

        if (is_null($screenCampaign)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($screenCampaign);
        $this->entityManager->flush();
    }
}
