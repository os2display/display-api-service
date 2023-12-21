<?php

namespace App\EventListener;

use App\Entity\Interfaces\TimestampableInterface;
use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Entity\Tenant\Slide;
use App\Entity\Tenant\Theme;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use function PHPUnit\Framework\assertEquals;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class RelationsModifiedAtListener
{
    const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private ?\DateTimeImmutable $modifiedAt;


    public function __construct()
    {
        $this->modifiedAt = new \DateTimeImmutable();
    }

    /**
     * @param PrePersistEventArgs $args
     * @return void
     */
    public final function prePersist(PrePersistEventArgs $args):void
    {
        $entity = $args->getObject();
        $class = get_class($entity);

        if ($entity instanceof TimestampableInterface) {
            $this->modifiedAt = $entity->getModifiedAt();
        }

        switch ($class) {
            case Feed::class:
                assert($entity instanceof Feed);
                $modifiedAt = [
                    'feedSource' => null,
                    'slide' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case Theme::class:
                assert($entity instanceof Theme);
                $modifiedAt = [
                    'logo' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case Slide::class:
                assert($entity instanceof Slide);
                $modifiedAt = [
                    'templateInfo' => null,
                    'theme' => null,
                    'media' => null,
                    'feed' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case PlaylistSlide::class:
                assert($entity instanceof PlaylistSlide);
                $modifiedAt = [
                    'slide' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case Playlist::class:
                assert($entity instanceof Playlist);
                $modifiedAt = [
                    'slides' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenCampaign::class:
                assert($entity instanceof ScreenCampaign);
                $modifiedAt = [
                    'campaign' => null,
                    'screen' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenGroupCampaign::class:
                assert($entity instanceof ScreenGroupCampaign);
                $modifiedAt = [
                    'campaign' => null,
                    'screenGroup' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenGroup::class:
                assert($entity instanceof ScreenGroup);
                $modifiedAt = [
                    'screenGroupCampaigns' => null,
                    'screens' => null
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case PlaylistScreenRegion::class:
                assert($entity instanceof PlaylistScreenRegion);
                $modifiedAt = [
                    'playlist' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenLayoutRegions::class:
                assert($entity instanceof ScreenLayoutRegions);
                $modifiedAt = [];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenLayout::class:
                assert($entity instanceof ScreenLayout);
                $modifiedAt = [
                    'regions' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case Screen::class:
                assert($entity instanceof Screen);
                $modifiedAt = [
                    'campaigns' => null,
                    'layout' => null,
                    'regions' => null,
                    'inScreenGroups' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
        }
    }

    /**
     * @param OnFlushEventArgs $args
     * @return void
     */
    public final function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $entities = array_merge($uow->getScheduledEntityUpdates(), $uow->getScheduledEntityInsertions());

        foreach ($entities as $entity) {
            if ($entity instanceof TimestampableInterface) {
                $this->modifiedAt = min($entity->getModifiedAt(), $this->modifiedAt);
            }
        }
    }

    /**
     * Executes multiple SQL queries to update relations_modified and relations_modified_at fields in the database.
     *
     * @param PostFlushEventArgs $args The PostFlushEventArgs object containing information about the event.
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public final function postFlush(PostFlushEventArgs $args): void
    {
        $connection = $args->getObjectManager()->getConnection();

        $sqlQueries = self::getUpdateRelationsAtQueries(withWhereClause: true);

        $rows = 0;
        foreach ($sqlQueries as $sqlQuery) {
            $stm = $connection->prepare($sqlQuery);
            $stm->bindValue('modified_at', $this->modifiedAt->format(self::DB_DATETIME_FORMAT));
            $rows += $stm->executeStatement();
        }
    }

    public static function getUpdateRelationsAtQueries(bool $withWhereClause = true): array
    {
        // Set SQL update queries for the "relations modified" fields on the parent (p), child (c) relationships up through the entity tree
        $sqlQueries = [];

        // Feed
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feedSource', parentTable: 'feed', childTable: 'feed_source', childHasRelations: false, withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'feed', childTable: 'slide', parentTableId: 'id', childTableId: 'feed_id', childHasRelations: false, withWhereClause: $withWhereClause);

        // Theme
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'logo', parentTable: 'theme', childTable: 'media', parentTableId: 'logo_id', childHasRelations: false, withWhereClause: $withWhereClause);

        // Slide
        $sqlQueries[] = self::getToManyQuery(jsonKey: 'media', parentTable: 'slide', pivotTable: 'slide_media', childTable: 'media', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'theme', parentTable: 'slide', childTable: 'theme', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'templateInfo', parentTable: 'slide', childTable: 'template', childHasRelations: false, withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feed', parentTable: 'slide', childTable: 'feed', withWhereClause: $withWhereClause);

        // PlaylistSlide
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'playlist_slide', childTable: 'slide', withWhereClause: $withWhereClause);

        // Playlist
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slides', parentTable: 'playlist', childTable: 'playlist_slide', parentTableId: 'id', childTableId: 'playlist_id', withWhereClause: $withWhereClause);

        // ScreenCampaign
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screen', parentTable: 'screen_campaign', childTable: 'screen', childHasRelations: false, withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - campaign
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_group_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);

        // ScreenGroup
        $sqlQueries[] = self::getToManyQuery(jsonKey: 'screens', parentTable: 'screen_group', pivotTable: 'screen_group_screen', childTable: 'screen', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screenGroupCampaigns', parentTable: 'screen_group', childTable: 'screen_group_campaign', parentTableId: 'id', childTableId: 'screen_group_id', withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - screenGroup
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screenGroup', parentTable: 'screen_group_campaign', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // PlaylistScreenRegion
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'playlist', parentTable: 'playlist_screen_region', childTable: 'playlist', withWhereClause: $withWhereClause);

        // ScreenLayoutRegions
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen_layout_regions', childTable: 'playlist_screen_region', parentTableId: 'id', childTableId: 'region_id', withWhereClause: $withWhereClause);

        // ScreenLayout
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen_layout', childTable: 'screen_layout_regions',  parentTableId: 'id', childTableId: 'screen_layout_id', withWhereClause: $withWhereClause);

        // Screen
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaigns', parentTable: 'screen', childTable: 'screen_campaign',  parentTableId: 'id', childTableId: 'screen_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'layout', parentTable: 'screen', childTable: 'screen_layout', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen', childTable: 'playlist_screen_region',  parentTableId: 'id', childTableId: 'screen_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToManyQuery(jsonKey: 'inScreenGroups', parentTable: 'screen', pivotTable: 'screen_group_screen', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // @TODO queries missing, refactor to static getQueries function and use in migrations

        return $sqlQueries;
    }

    private static function getToOneQuery(string $jsonKey, string $parentTable, string $childTable, ?string $parentTableId = null, string $childTableId = 'id', bool $childHasRelations = true, bool $withWhereClause = true): string
    {
        $parentTableId = (null === $parentTableId) ? $childTable.'_id' : $parentTableId;

        $queryFormat = 'UPDATE %s p INNER JOIN %s c ON p.%s = c.%s
                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), %s), \'%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.%s", %s)';

        $childGreatest = $childHasRelations ? 'c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')' : 'c.modified_at';
        $jsonGreatest = $childHasRelations ? 'DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\')' : 'c.modified_at';
        $sqlDateFormat = '%Y-%m-%d %H:%i:%s';

        $query = sprintf($queryFormat, $parentTable, $childTable, $parentTableId, $childTableId, $childGreatest, $sqlDateFormat, $jsonKey, $jsonGreatest);

        if ($withWhereClause) {
            $query .= ' WHERE p.modified_at >= :modified_at OR c.modified_at >= :modified_at';

            if ($childHasRelations) {
                $query .= ' OR c.relations_modified_at >= :modified_at';
            }
        }

        return $query;
    }

    private static function getToManyQuery(string $jsonKey, string $parentTable, string $pivotTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';
        $childTableId = $childTable.'_id';
        $dbDateFormat = '%Y-%m-%d %H:%i:%s';

        $queryFormat = 'UPDATE %s p INNER JOIN (SELECT pivot.%s, max(c.modified_at) as max_modified_at
                           FROM %s pivot INNER JOIN %s c ON pivot.%s=c.id GROUP BY pivot.%s) temp ON p.id = temp.%s
                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), temp.max_modified_at), \'%s\'),
                    p.relations_modified = JSON_SET(p.relations_modified, "$.%s", max_modified_at)';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $pivotTable, $childTable, $childTableId, $parentTableId, $parentTableId, $dbDateFormat, $jsonKey);
        if ($withWhereClause) {
            $query .= ' WHERE p.modified_at >= :modified_at OR max_modified_at >= :modified_at';
        }

        return $query;
    }

}
