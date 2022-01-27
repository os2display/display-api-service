<?php

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Playlist;
use App\Entity\Screen;
use App\Entity\ScreenCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
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

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScreenCampaign::class);

        $this->entityManager = $this->getEntityManager();
    }


    public function getScreenCampaignsBasedOnScreen(Ulid $screenUlid, int $page = 1, int $itemsPerPage = 10): Paginator
    {
        $firstResult = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->select('sp')
            ->where('sp.screen = :screenId')
            ->setParameter('screenId', $screenUlid, 'ulid');

        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function updateRelations(Ulid $screenUlid, ArrayCollection $collection)
    {
        $screensRepos = $this->entityManager->getRepository(Screen::class);
        $screen = $screensRepos->findOneBy(['id' => $screenUlid]);
        if (is_null($screen)) {
            throw new InvalidArgumentException('Screen not found');
        }

        $playlistRepos = $this->entityManager->getRepository(Playlist::class);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Remove all existing relations between playlists/campaigns and current screen.
            $entities = $this->findBy(['screen' => $screen]);
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            foreach ($collection as $entity) {
                $campaign = $playlistRepos->findOneBy(['id' => $entity->campaign]); //todosine
                if (is_null($campaign)) {
                    throw new InvalidArgumentException('Campaign not found');
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
        $screenCampaign = $this->findOneBy(['screen' => $ulid, 'campaign' => $campaignUlid]);

        if (is_null($screenCampaign)) {
            throw new InvalidArgumentException('Relation not found');
        }

        $this->entityManager->remove($screenCampaign);
        $this->entityManager->flush();
    }
}
