<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroupCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Screen|null find($id, $lockMode = null, $lockVersion = null)
 * @method Screen|null findOneBy(array $criteria, array $orderBy = null)
 * @method Screen[]    findAll()
 * @method Screen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\Tenant\Screen>
 */
class ScreenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Screen::class);
    }

    public function getScreensByScreenGroupId(Ulid $screenGroupUlid): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('sgr')
            ->innerJoin('sgr.screenGroups', 's', Join::WITH, 's.id = :screenGroupId')
            ->setParameter('screenGroupId', $screenGroupUlid, 'ulid');

        return $queryBuilder;
    }

    /**
     * Get total or active campaign counts for a screen via a single SQL query.
     *
     * Collects campaigns from both direct screen assignments (screen_campaign)
     * and indirect assignments through screen groups (screen_group_campaign),
     * then counts distinct campaigns and filters for currently active ones
     * based on published_from/published_to dates if $active is true.
     *
     * @return int number of campaigns for the screen
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getCampaignCountForScreen(Ulid $screenId, bool $activeCampaigns = false): int
    {
        $em = $this->getEntityManager();
        $now = new \DateTime();

        // Subquery: all campaign IDs for this screen (direct + via groups)
        $directQb = $em->createQueryBuilder()
            ->select('IDENTITY(sc.campaign)')
            ->from(ScreenCampaign::class, 'sc')
            ->where('sc.screen = :screenId');

        $groupQb = $em->createQueryBuilder()
            ->select('IDENTITY(sgc.campaign)')
            ->from(ScreenGroupCampaign::class, 'sgc')
            ->innerJoin('sgc.screenGroup', 'sg')
            ->innerJoin('sg.screens', 's')
            ->where('s.id = :screenId');

        $qb = $em->createQueryBuilder()
            ->select('COUNT(DISTINCT p.id)')
            ->from(Playlist::class, 'p')
            ->where('p.id IN ('.$directQb->getDQL().') OR p.id IN ('.$groupQb->getDQL().')')
            ->setParameter('screenId', $screenId, 'ulid');

        if ($activeCampaigns) {
            $qb->andWhere('p.publishedFrom IS NULL OR p.publishedFrom < :now')
                ->andWhere('p.publishedTo IS NULL OR p.publishedTo > :now')
                ->setParameter('now', $now);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
