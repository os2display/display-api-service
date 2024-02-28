<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Schedule;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Entity\Tenant\Slide;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Listener class for updating relationsModified field in the database.
 *
 * The purpose of this class is to propagate checksums for the last update to child nodes in the
 * entity relationship tree from the bottom up the tree updating the "relationsModified" fields.
 *
 * "relationsModified" contains a json object with a checksum for the for any relation the entity has.
 * This must be a checksum of "id", "version" and "relations_modified to ensure the checksum changes.
 * This json object exposed in the API to allow the clients to know when it's necessary to fetch updated
 * to relations.
 *
 * Example (Screen):
 *  "relationsModified": {
 *      "campaigns": "cf9bb7d5fd04743dd21b5e3361db7eed575258e0",
 *      "layout": "4dc925b9043b9d151607328ab2d022610583777f",
 *      "regions": "278df93a0dc5309e0db357177352072d86da0d29",
 *      "inScreenGroups": "bf0d49f6af71ac74da140e32243f3950219bb29c"
 *  }
 *
 * For efficacy this is implemented in raw SQL
 *  - we don't want to load all relations in the doctrine layer to avoid the performance hit and
 *    high memory footprint
 *  - we don't use doctrines (DBAL's) query builder because we depend on SQL functions like GROUP_CONCAT()
 *    and CAST() that are not supported by the query builder.
 */
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class RelationsChecksumListener
{
    private const CHECKSUM_TABLES = ['feed_source', 'feed', 'slide', 'media', 'theme', 'template', 'playlist_slide',
                                    'playlist', 'screen_campaign', 'screen', 'screen_group_campaign', 'screen_group',
                                    'playlist_screen_region', 'screen_layout_regions', 'screen_layout'];

    /**
     * PrePersist listener.
     *
     * This will set the initial json object for the relationsModified field.
     * All checksums are set to null because the correct value will be set
     * in the postFlush handler. But we must set proper keys for the "JSON_SET"
     * function to work. If the field is left as null it will be serialized to the
     * database as `[]` preventing JSON_SET from updating the field.
     *
     * @return void
     */
    final public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        switch ($entity::class) {
            case Feed::class:
                $modifiedAt = [
                    'feedSource' => null,
                    'slide' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Slide::class:
                $modifiedAt = [
                    'templateInfo' => null,
                    'theme' => null,
                    'media' => null,
                    'feed' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case PlaylistSlide::class:
                $modifiedAt = [
                    'slide' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Playlist::class:
                $modifiedAt = [
                    'slides' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenCampaign::class:
                $modifiedAt = [
                    'campaign' => null,
                    'screen' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenGroupCampaign::class:
                $modifiedAt = [
                    'campaign' => null,
                    'screenGroup' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenGroup::class:
                $modifiedAt = [
                    'screenGroupCampaigns' => null,
                    'screens' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case PlaylistScreenRegion::class:
                $modifiedAt = [
                    'playlist' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenLayoutRegions::class:
                $modifiedAt = [];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenLayout::class:
                $modifiedAt = [
                    'regions' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Screen::class:
                $modifiedAt = [
                    'campaigns' => null,
                    'layout' => null,
                    'regions' => null,
                    'inScreenGroups' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
        }
    }

    /**
     * PreUpdate listener.
     *
     * On update set "changed" to "true" to ensure checksum changes propagate up the tree.
     *
     * @return void
     */
    final public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof RelationsChecksumInterface) {
            $entity->setChanged(true);
        }
    }

    /**
     * PreRemove listener.
     *
     * For "toMany" relations the "preUpdate" listener will not be called for the parent if a child relations
     * is deleted by calling remove() on the entity manager. We need to manually set "changed" on the parent
     * to "true" to ensure checksum changes propagate up the tree.
     *
     * @return void
     */
    final public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        switch ($entity::class) {
            case ScreenLayoutRegions::class:
                $entity->getScreenLayout()?->setChanged(true);
                break;
            case ScreenGroup::class:
                foreach ($entity->getScreens() as $screen) {
                    $screen->setChanged(true);
                }
                break;
            case ScreenCampaign::class:
                $entity->getScreen()->setChanged(true);
                break;
            case PlaylistScreenRegion::class:
                $entity->getScreen()?->setChanged(true);
                break;
            case ScreenGroupCampaign::class:
                $entity->getScreenGroup()->setChanged(true);
                break;
            case Schedule::class:
                $entity->getPlaylist()?->setChanged(true);
                break;
            case PlaylistSlide::class:
                $entity->getPlaylist()->setChanged(true);
                break;
            case Media::class:
                foreach ($entity->getSlides() as $slide) {
                    $slide->setChanged(true);
                }
                break;
        }
    }

    /**
     * PostFlush listener.
     *
     * Executes update SQL queries to set changed and relations_checksum fields in the database.
     *
     * @param PostFlushEventArgs $args the PostFlushEventArgs object containing information about the event
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    final public function postFlush(PostFlushEventArgs $args): void
    {
        $connection = $args->getObjectManager()->getConnection();

        $sqlQueries = self::getUpdateRelationsAtQueries(withWhereClause: true);

        $connection->beginTransaction();

        try {
            foreach ($sqlQueries as $sqlQuery) {
                $stm = $connection->prepare($sqlQuery);
                $stm->executeStatement();
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Get an array of SQL update statements to update the changed and relationsModified fields.
     *
     * @param bool $withWhereClause
     *   Should the statements include a where clause to limit the statement
     *
     * @return string[]
     *   Array of SQL statements
     */
    public static function getUpdateRelationsAtQueries(bool $withWhereClause = true): array
    {
        // Set SQL update queries for the "relations checksum" fields on the parent (p), child (c) relationships up through the entity tree
        $sqlQueries = [];

        // Feed
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feedSource', parentTable: 'feed', childTable: 'feed_source', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'feed', childTable: 'slide', parentTableId: 'id', childTableId: 'feed_id', withWhereClause: $withWhereClause);

        // Slide
        $sqlQueries[] = self::getManyToManyQuery(jsonKey: 'media', parentTable: 'slide', pivotTable: 'slide_media', childTable: 'media', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'theme', parentTable: 'slide', childTable: 'theme', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'templateInfo', parentTable: 'slide', childTable: 'template', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feed', parentTable: 'slide', childTable: 'feed', withWhereClause: $withWhereClause);

        // PlaylistSlide
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'playlist_slide', childTable: 'slide', withWhereClause: $withWhereClause);

        // Playlist
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'slides', parentTable: 'playlist', childTable: 'playlist_slide', withWhereClause: $withWhereClause);

        // ScreenCampaign
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screen', parentTable: 'screen_campaign', childTable: 'screen', withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - campaign
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_group_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);

        // ScreenGroup
        $sqlQueries[] = self::getManyToManyQuery(jsonKey: 'screens', parentTable: 'screen_group', pivotTable: 'screen_group_screen', childTable: 'screen', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'screenGroupCampaigns', parentTable: 'screen_group', childTable: 'screen_group_campaign', withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - screenGroup
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screenGroup', parentTable: 'screen_group_campaign', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // PlaylistScreenRegion
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'playlist', parentTable: 'playlist_screen_region', childTable: 'playlist', withWhereClause: $withWhereClause);

        // ScreenLayoutRegions
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen_layout_regions', childTable: 'playlist_screen_region', parentTableId: 'id', childTableId: 'region_id', withWhereClause: $withWhereClause);

        // ScreenLayout
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'regions', parentTable: 'screen_layout', childTable: 'screen_layout_regions', withWhereClause: $withWhereClause);

        // Screen
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'campaigns', parentTable: 'screen', childTable: 'screen_campaign', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'layout', parentTable: 'screen', childTable: 'screen_layout', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'regions', parentTable: 'screen', childTable: 'playlist_screen_region', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getManyToManyQuery(jsonKey: 'inScreenGroups', parentTable: 'screen', pivotTable: 'screen_group_screen', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // Add reset 'changed' fields queries
        $sqlQueries = array_merge($sqlQueries, self::getResetChangedQueries());

        return $sqlQueries;
    }

    /**
     * Get "One/ManyToOne" query.
     *
     * For a table (parent) that has a relation to another table (child) where we need to update the "relations_checksum"
     * field on the parent with a checksum of values from the child we need to join the tables and set the values.
     *
     * Basically we do: "Update parent, join child, set parent value = SHA(child values)"
     *
     * Example:
     *  UPDATE slide p
     *      INNER JOIN theme c ON p.theme_id = c.id
     *  SET p.changed = 1,
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.theme", SHA1(CONCAT(c.id, c.version, c.relations_checksum)))
     *  WHERE
     *      p.changed = 1
     *      OR c.changed = 1
     *
     * Explanation:
     *   UPDATE parent table p, INNER JOIN child table c
     *      - use INNER JOIN because the query only makes sense for result where both parent and child tables have rows
     *   SET changed to 1 (true) to enable propagation up the tree.
     *   SET the value for the relevant json key on the json object in p.relations_checksum to the checksum of the child id, version and relations checksum
     *   WHERE either p.changed or c.changed is true
     *     - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the bool "changed" as clause in WHERE to limit to only update the rows just modified.
     *
     * @param string|null $parentTableId
     *
     * @return string
     */
    private static function getToOneQuery(string $jsonKey, string $parentTable, string $childTable, ?string $parentTableId = null, string $childTableId = 'id', bool $withWhereClause = true): string
    {
        // Set the column name to use for "ON" in the Join clause. By default, the child table name with "_id" appended.
        // E.g. "UPDATE feed p INNER JOIN feed_source c ON p.feed_source_id = c.id"
        $parentTableId ??= $childTable.'_id';

        // The base UPDATE query.
        // - Use INNER JON to only select rows that have a match in both parent and child tables
        // - Use JSON_SET to only INSERT/UPDATE the relevant key in the json object, not the whole field.
        $queryFormat = '
            UPDATE %s p 
                INNER JOIN %s c ON p.%s = c.%s
                SET p.changed = 1, 
                    p.relations_checksum = JSON_SET(p.relations_checksum, "$.%s", SHA1(CONCAT(c.id, c.version, c.relations_checksum)))
                ';

        $query = sprintf($queryFormat, $parentTable, $childTable, $parentTableId, $childTableId, $jsonKey);

        // Add WHERE clause to only update rows that have been modified since ":modified_at"
        if ($withWhereClause) {
            $query .= ' WHERE p.changed = 1 OR c.changed = 1';
        }

        return $query;
    }

    /**
     * Get "OnetoMany" query.
     *
     * For a table (parent) that has a toMany relationship to another table (child) where we need to update the "relations_checksum"
     * field on the parent with a checksum of values from the child we need to join the tables and set the values.
     *
     * Example:
     *  UPDATE
     *      playlist p
     *  INNER JOIN (
     *      SELECT
     *          c.playlist_id,
     *          CAST(GROUP_CONCAT(c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
     *          SHA1(GROUP_CONCAT(c.id, c.version, c.relations_checksum)) checksum
     *      FROM
     *          playlist_slide c
     *      GROUP BY
     *          c.playlist_id
     *      ) temp ON p.id = temp.playlist_id
     *  SET p.changed = 1,
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.slides", temp.checksum)
     *  WHERE p.changed = 1 OR temp.changed = 1
     *
     * Explanation:
     *   Because this is a "to many" relation we need to GROUP_CONCAT values from the child relations. This is done in a temporary table
     *   with GROUP BY parent id in the child table. This gives us just one child row for each parent row with a checksum from the relevant
     *   fields across all child rows.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.changed and p.relations_checksum values on the parent.
     *    - Because GROUP_CONCAT will give us all child rows "changed" as one, e.g. "00010001" we need "> 0" to ecaluate to true/false
     *      and then CAST that to "unsigned" to get a TINYINT (bool)
     *   WHERE either p.changed or c.changed is true
     *    - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the bool "changed" as clause in
     *      WHERE to limit to only update the rows just modified.
     *
     * @return string
     */
    private static function getOneToManyQuery(string $jsonKey, string $parentTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';

        $queryFormat = '
            UPDATE 
                %s p
                INNER JOIN (
                    SELECT 
                        c.%s, 
                        CAST(GROUP_CONCAT(c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
                        SHA1(GROUP_CONCAT(c.id, c.version, c.relations_checksum)) checksum
                    FROM 
                        %s c 
                    GROUP BY 
                        c.%s
                ) temp ON p.id = temp.%s
                SET p.changed = 1,
                    p.relations_checksum = JSON_SET(p.relations_checksum, "$.%s", temp.checksum)
                ';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $childTable, $parentTableId, $parentTableId, $jsonKey);

        if ($withWhereClause) {
            $query .= ' WHERE p.changed = 1 OR temp.changed = 1';
        }

        return $query;
    }

    /**
     * Get "many to many" query.
     *
     * For a table (parent) that has a relation to another table (child) through a pivot table where we need to update the "changed"
     * and "relations_checksum" fields on the parent with values from the child we need to join the tables and set the values.
     *
     * Basically we do:
     *  "Update parent, join temp (SELECT checksum of c.id, c.version, c.relations_checksum from the child rows with GROUP_CONCAT), set parent values = child values"
     *
     * Example:
     *  UPDATE
     *      slide p
     *  INNER JOIN (
     *      SELECT
     *          pivot.slide_id,
     *          CAST(GROUP_CONCAT(c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
     *          SHA1(GROUP_CONCAT(c.id, c.version, c.relations_checksum)) checksum
     *      FROM
     *          slide_media pivot
     *      INNER JOIN media c ON pivot.media_id = c.id
     *      GROUP BY
     *          pivot.slide_id
     *  ) temp ON p.id = temp.slide_id
     *  SET p.changed = 1,
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.media", temp.checksum)
     *  WHERE p.changed = 1 OR temp.changed = 1
     *
     * Explanation:
     *   Because this is a "to many" relation we need to GROUP_CONCAT values from the child relations. This is done in a temporary table
     *   with GROUP BY parent id in the child table. This gives us just one child row for each parent row with a checksum from the relevant
     *   fields across all child rows.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.changed and p.relations_checksum values on the parent.
     *    - Because GROUP_CONCAT will give us all child rows "changed" as one, e.g. "00010001" we need "> 0" to ecaluate to true/false
     *      and then CAST that to "unsigned" to get a TINYINT (bool)
     *   WHERE either p.changed or c.changed is true
     *    - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the bool "changed" as clause in
     *      WHERE to limit to only update the rows just modified.
     *
     * @return string
     */
    private static function getManyToManyQuery(string $jsonKey, string $parentTable, string $pivotTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';
        $childTableId = $childTable.'_id';

        $queryFormat = '
            UPDATE
                %s p
                INNER JOIN (
                    SELECT
                        pivot.%s,
                        CAST(GROUP_CONCAT(c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
                        SHA1(GROUP_CONCAT(c.id, c.version, c.relations_checksum)) checksum
                    FROM
                        %s pivot
                        INNER JOIN %s c ON pivot.%s = c.id
                    GROUP BY
                        pivot.%s
                ) temp ON p.id = temp.%s 
                SET p.changed = 1, 
                    p.relations_checksum = JSON_SET(p.relations_checksum, "$.%s", temp.checksum)
                ';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $pivotTable, $childTable, $childTableId, $parentTableId, $parentTableId, $jsonKey);
        if ($withWhereClause) {
            $query .= ' WHERE p.changed = 1 OR temp.changed = 1';
        }

        return $query;
    }

    /**
     * Get an array of queries to reset all "changed" fields to 0.
     *
     * Example:
     *   UPDATE screen SET screen.changed = 0 WHERE screen.changed = 1;
     *
     * @return array
     */
    private static function getResetChangedQueries(): array
    {
        $queries = [];
        foreach (self::CHECKSUM_TABLES as $table) {
            $queries[] = sprintf('UPDATE %s SET changed = 0 WHERE changed = 1', $table);
        }

        return $queries;
    }
}
