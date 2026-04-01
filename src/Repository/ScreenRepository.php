<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant\Screen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Screen|null find($id, $lockMode = null, $lockVersion = null)
 * @method Screen|null findOneBy(array $criteria, array $orderBy = null)
 * @method Screen[]    findAll()
 * @method Screen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * Get total and active campaign counts for a screen via a single SQL query.
     *
     * Collects campaigns from both direct screen assignments (screen_campaign)
     * and indirect assignments through screen groups (screen_group_campaign),
     * then counts distinct campaigns and filters for currently active ones
     * based on published_from/published_to dates.
     *
     * @return array{total: int, active: int}
     */
    public function getCampaignCountsForScreen(Ulid $screenId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $sql = <<<'SQL'
            SELECT
                COUNT(DISTINCT p.id) AS total,
                COUNT(DISTINCT CASE
                    WHEN (p.published_from IS NULL OR p.published_from < :now)
                     AND (p.published_to IS NULL OR p.published_to > :now)
                    THEN p.id
                END) AS active
            FROM (
                -- Campaigns directly assigned to the screen
                SELECT sc.campaign_id
                FROM screen_campaign sc
                WHERE sc.screen_id = :screenId
                UNION
                -- Campaigns assigned via screen groups the screen belongs to
                SELECT sgc.campaign_id
                FROM screen_group_campaign sgc
                INNER JOIN screen_group_screen sgs ON sgs.screen_group_id = sgc.screen_group_id
                WHERE sgs.screen_id = :screenId
            ) AS all_campaigns
            INNER JOIN playlist p ON p.id = all_campaigns.campaign_id
            SQL;

        $result = $conn->executeQuery($sql, [
            'screenId' => $screenId->toBinary(),
            'now' => $now,
        ])->fetchAssociative();

        return [
            'total' => (int) $result['total'],
            'active' => (int) $result['active'],
        ];
    }
}
