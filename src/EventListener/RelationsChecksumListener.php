<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\RelationsChecksumInterface;
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
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Listener class for updating relationsModified and relationsModifiedAt fields in the database.
 *
 * The purpose of this class is to propagate timestamp for the last update to child nodes in the
 * entity relationship tree from the bottom up the tree updating the "relationsModified" and
 * "relationsModifiedAt" fields.
 *
 * "relationsModified" contains a json object with a timestamp for the latest change for any
 * relation the entity has. This must be the latest timestamp for any change either in a child
 * or sub child. This json object is exposed in the API to allow the clients to know when it's
 * necessary to fetch updated to relations.
 *
 * Example (Screen):
 *  "relationsModified": {
 *      "campaigns": "2024-01-02T11:49:08.000Z",
 *      "layout": "2024-01-02T11:49:13.000Z",
 *      "regions": "2024-01-02T11:49:13.000Z",
 *      "inScreenGroups": "2024-01-02T11:49:05.000Z"
 *  }
 *
 * "relationsModifiedAt" is a timestamp that must contain the latest timestamp contained in the
 * "relationsModified" object. This is not exposed in the API but is used as a criteria in WHERE
 * statements as the fields are updated going from the bottom of the tree and up.
 *
 * For efficacy this is implemented in raw SQL
 *  - we don't want to load all relations in the doctrine layer to avoid the performance hit and
 *    high memory footprint
 *  - we don't use doctrines (DBAL's) query builder because we depend on SQL functions like GREATEST()
 *    and MAX() that are not supported by the query builder.
 */
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::preUpdate)]
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
     * @param PrePersistEventArgs $args
     *
     * @return void
     */
    final public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $class = get_class($entity);

        switch ($class) {
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

    final public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof RelationsChecksumInterface) {
            $entity->setChanged(true);
        }
    }

    /**
     * PostFlush listener.
     *
     * Executes update SQL queries to set relations_checksum and relations_checksum_at fields in the database.
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

        $sqlQueries = self::getUpdateRelationsAtQueries(withWhereClause: false);

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
     * Get an array of SQL update statements to update the relationsModified fields.
     *
     * @param bool $withWhereClause
     *   Should the statements include a where clause to limit the statement
     *
     * @return string[]
     *   Array of SQL statements
     */
    public static function getUpdateRelationsAtQueries(bool $withWhereClause = true): array
    {
        // Set SQL update queries for the "relations modified" fields on the parent (p), child (c) relationships up through the entity tree
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
     * For a table (parent) that has a relation to another table (child) where we need to update the "relations_checksum_at"
     * and "relations_checksum" fields on the parent with values from the child we need to join the tables and set the values.
     *
     * Basically we do: "Update parent, join child, set parent values = child values"
     *
     * Example:
     *  UPDATE
     *      slide p
     *  INNER JOIN
     *      theme c
     *  ON
     *      p.theme_id = c.id
     *  SET
     *      p.relations_checksum_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_checksum_at, '1970-01-01 00:00:00'), c.modified_at, COALESCE(c.relations_checksum_at, '1970-01-01 00:00:00')), '%Y-%m-%d %H:%i:%s'),
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.theme", DATE_FORMAT(GREATEST(COALESCE(c.relations_checksum_at, '1970-01-01 00:00:00'), c.modified_at), '%Y-%m-%d %H:%i:%s'))
     *  WHERE
     *      p.modified_at >= :modified_at OR c.modified_at >= :modified_at OR c.relations_checksum_at >= :modified_at
     *
     * Explanation:
     *   UPDATE parent table p, INNER JOIN child table c
     *      - use INNER JOIN because the query only makes sense for result where both parent and child tables have rows
     *   SET p.relations_checksum_at to be the GREATEST (latest) value of either p.relations_checksum_at, c.modified_at and c.relations_checksum_at
     *   SET the value for the relevant json key on the json object in p.relations_checksum to the GREATEST (latest) value of either c.modified_at and c.relations_checksum_at
     *     - Because "relations_checksum_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
     *     - Because GREATEST() will return a date in numeric format we need to use DATE_FORMAT() to ensure consistent date formats
     *   WHERE either p.modified_at or c.modified_at is greater than a given timestamp
     *     - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the modified_at timestamps as clause in WHERE to limit to only update the rows just modified.
     *
     * @param string $jsonKey
     * @param string $parentTable
     * @param string $childTable
     * @param string|null $parentTableId
     * @param string $childTableId
     * @param bool $childHasRelations
     * @param bool $withWhereClause
     *
     * @return string
     */
    private static function getToOneQuery(string $jsonKey, string $parentTable, string $childTable, string $parentTableId = null, string $childTableId = 'id', bool $withWhereClause = true): string
    {
        // Set the column name to use for "ON" in the Join clause. By default, the child table name with "_id" appended.
        // E.g. "UPDATE feed p INNER JOIN feed_source c ON p.feed_source_id = c.id"
        $parentTableId = (null === $parentTableId) ? $childTable.'_id' : $parentTableId;

        // The base UPDATE query.
        // - Use INNER JON to only select rows that have a match in both parent and child tables
        // - Use JSON_SET to only INSERT/UPDATE the relevant key in the json object, not the whole field.
        $queryFormat = 'UPDATE %s p INNER JOIN %s c ON p.%s = c.%s
                        SET p.changed = 1, 
                        p.relations_checksum = JSON_SET(p.relations_checksum, "$.%s", SHA1(CONCAT(c.id, c.version, c.relations_checksum)))';

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
     * For a table (parent) that has a toMany relationship to another table (child) where we need to update the "relations_checksum_at"
     *  and "relations_checksum" fields on the parent with values from the child we need to join the tables and set the values.
     *
     * Example:
     *  UPDATE
     *      playlist p
     *          INNER JOIN (
     *              SELECT
     *                  c.playlist_id, MAX(GREATEST(COALESCE(c.relations_checksum_at, '1970-01-01 00:00:00'), c.modified_at)) greatest
     *              FROM
     *                  playlist_slide c
     *              GROUP BY
     *                  c.playlist_id
     *          ) temp
     *          ON
     *              p.id = temp.playlist_id
     *  SET
     *      p.relations_checksum_at = temp.greatest,
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.slides", DATE_FORMAT(temp.greatest, '%Y-%m-%d %H:%i:%s'))
     *  WHERE
     *      p.modified_at >= :modified_at OR c.modified_at >= :modified_at OR c.relations_checksum_at >= :modified_at
     *
     * Explanation:
     *   Because this is a "to many" relation we need to SELECT the MAX (latest) modified_at timestamp from the child relations. This is done in a temporary table
     *   with SELECT max() and GROUP BY parent id in the child table. This gives us just one child row for each parent row with the latest timestamp.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.relations_checksum_at and p.relations_checksum values on the parent.
     *    - Because "relations_checksum_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
     *    - Because GREATEST() will return a date in numeric format we need to use DATE_FORMAT() to ensure consistent date formats
     *   WHERE either p.modified_at or (child) max_modified_at is greater than a given timestamp
     *    - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the modified_at timestamps as clause in WHERE to limit to only update the rows just modified.
     *
     * @param string $jsonKey
     * @param string $parentTable
     * @param string $childTable
     * @param bool $withWhereClause
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
                    p.relations_checksum = JSON_SET(p.relations_checksum, "$.%s", temp.checksum)';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $childTable, $parentTableId, $parentTableId, $jsonKey);

        if ($withWhereClause) {
            $query .= ' WHERE p.changed = 1 OR temp.changed = 1';
        }

        return $query;
    }

    /**
     * Get "many to many" query.
     *
     * For a table (parent) that has a relation to another table (child) through a pivot table where we need to update the "relations_checksum_at"
     * and "relations_checksum" fields on the parent with values from the child we need to join the tables and set the values.
     *
     * Basically we do:
     *  "Update parent, join temp (SELECT id and c.modified_at from the child row with the MAX (latest) modified_at), set parent values = child values"
     *
     * Example:
     *  UPDATE
     *      slide p
     *          INNER JOIN (
     *              SELECT
     *                  pivot.slide_id, max(c.modified_at) as max_modified_at
     *              FROM
     *                  slide_media pivot
     *              INNER JOIN
     *                  media c ON pivot.media_id=c.id
     *              GROUP BY
     *                  pivot.slide_id
     *          ) temp
     *          ON
     *              p.id = temp.slide_id
     *  SET
     *      p.relations_checksum_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_checksum_at, '1970-01-01 00:00:00'), temp.max_modified_at), '%Y-%m-%d %H:%i:%s'),
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.media", max_modified_at)
     *  WHERE
     *      p.modified_at >= :modified_at OR max_modified_at >= :modified_at
     *
     * Explanation:
     *   Because this is a "to many" relation we need to SELECT the MAX (latest) modified_at timestamp from the child relations. This is done in a temporary table
     *   with SELECT max() and GROUP BY parent id in the pivot table. This gives us just one child row for each parent row with the latest timestamp.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.relations_checksum_at and p.relations_checksum values on the parent.
     *    - Because "relations_checksum_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
     *    - Because GREATEST() will return a date in numeric format we need to use DATE_FORMAT() to ensure consistent date formats
     *   WHERE either p.modified_at or (child) max_modified_at is greater than a given timestamp
     *    - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the modified_at timestamps as clause in WHERE to limit to only update the rows just modified.
     *
     * @param string $jsonKey
     * @param string $parentTable
     * @param string $pivotTable
     * @param string $childTable
     * @param bool $withWhereClause
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
     * Get an array of queries to rest all "changed" fields to 0.
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
