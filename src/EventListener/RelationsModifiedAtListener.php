<?php

declare(strict_types=1);

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
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class RelationsModifiedAtListener
{
    public const DB_DATETIME_FORMAT_PHP = 'Y-m-d H:i:s';
    public const DB_DATETIME_FORMAT_SQL  = '%Y-%m-%d %H:%i:%s';
    private ?\DateTimeImmutable $modifiedAt;

    private array $entities;

    public function __construct()
    {
        $this->modifiedAt = new \DateTimeImmutable();
        $this->entities = [];
    }

    /**
     * PrePersist listener.
     *
     * This will set the initial json object for the relationsModified field.
     * All timestamps are set to null because the correct value will be set
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
                    'slide' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case Playlist::class:
                assert($entity instanceof Playlist);
                $modifiedAt = [
                    'slides' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenCampaign::class:
                assert($entity instanceof ScreenCampaign);
                $modifiedAt = [
                    'campaign' => null,
                    'screen' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenGroupCampaign::class:
                assert($entity instanceof ScreenGroupCampaign);
                $modifiedAt = [
                    'campaign' => null,
                    'screenGroup' => null,
                ];
                $entity->setRelationsModified($modifiedAt);
                break;
            case ScreenGroup::class:
                assert($entity instanceof ScreenGroup);
                $modifiedAt = [
                    'screenGroupCampaigns' => null,
                    'screens' => null,
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
     * OnFlush listener.
     *
     * Get the oldest ´modifiedAt` timestamp from the changed entities.
     * This is needed in the where clause in the postFlush update statements.
     *
     * @param OnFlushEventArgs $args
     *
     * @return void
     */
    final public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $this->entities = array_merge($uow->getScheduledEntityUpdates(), $uow->getScheduledEntityInsertions());

        foreach ($this->entities as $entity) {
            if ($entity instanceof TimestampableInterface) {
                $this->modifiedAt = min($entity->getModifiedAt(), $this->modifiedAt);
            }
        }
    }

    /**
     * PostFlush listener.
     *
     * Executes update SQL queries to set relations_modified and relations_modified_at fields in the database.
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

        $rows = 0;
        foreach ($sqlQueries as $sqlQuery) {
            $stm = $connection->prepare($sqlQuery);
            $stm->bindValue('modified_at', $this->modifiedAt->format(self::DB_DATETIME_FORMAT_PHP));
            $rows += $stm->executeStatement();
        }

        // DB has been altered outside the ORM resulting in stale in-memory data.
        // Refresh all entities loaded in the manager
        foreach ($this->entities as $entity) {
            $args->getObjectManager()->refresh($entity);
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
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feedSource', parentTable: 'feed', childTable: 'feed_source', childHasRelations: false, withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'feed', childTable: 'slide', parentTableId: 'id', childTableId: 'feed_id', childHasRelations: false, withWhereClause: $withWhereClause);

        // Slide
        $sqlQueries[] = self::getManyToManyQuery(jsonKey: 'media', parentTable: 'slide', pivotTable: 'slide_media', childTable: 'media', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'theme', parentTable: 'slide', childTable: 'theme', childHasRelations: false, withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'templateInfo', parentTable: 'slide', childTable: 'template', childHasRelations: false, withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'feed', parentTable: 'slide', childTable: 'feed', withWhereClause: $withWhereClause);

        // PlaylistSlide
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'slide', parentTable: 'playlist_slide', childTable: 'slide', withWhereClause: $withWhereClause);

        // Playlist
        $sqlQueries[] = self::getOneToManyQuery(jsonKey: 'slides', parentTable: 'playlist', childTable: 'playlist_slide', withWhereClause: $withWhereClause);

        // ScreenCampaign
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'screen', parentTable: 'screen_campaign', childTable: 'screen', childHasRelations: false, withWhereClause: $withWhereClause);

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

        return $sqlQueries;
    }

    /**
     * Get "One/ManyToOne" query.
     *
     * For a table (parent) that has a relation to another table (child) where we need to update the "relations_modified_at"
     * and "relations_modified" fields on the parent with values from the child we need to join the tables and set the values.
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
     *      p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, '1970-01-01 00:00:00'), c.modified_at, COALESCE(c.relations_modified_at, '1970-01-01 00:00:00')), '%Y-%m-%d %H:%i:%s'),
     *      p.relations_modified = JSON_SET(p.relations_modified, "$.theme", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, '1970-01-01 00:00:00'), c.modified_at), '%Y-%m-%d %H:%i:%s'))
     *  WHERE
     *      p.modified_at >= :modified_at OR c.modified_at >= :modified_at OR c.relations_modified_at >= :modified_at
     *
     * Explanation:
     *   UPDATE parent table p, INNER JOIN child table c
     *      - use INNER JOIN because the query only makes sense for result where both parent and child tables have rows
     *   SET p.relations_modified_at to be the GREATEST (latest) value of either p.relations_modified_at, c.modified_at and c.relations_modified_at
     *   SET the value for the relevant json key on the json object in p.relations_modified to the GREATEST (latest) value of either c.modified_at and c.relations_modified_at
     *     - Because "relations_modified_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
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
    private static function getToOneQuery(string $jsonKey, string $parentTable, string $childTable, string $parentTableId = null, string $childTableId = 'id', bool $childHasRelations = true, bool $withWhereClause = true): string
    {
        // Set the column name to use for "ON" in the Join clause. By default, the child table name with "_id" appended.
        // E.g. "UPDATE feed p INNER JOIN feed_source c ON p.feed_source_id = c.id"
        $parentTableId = (null === $parentTableId) ? $childTable.'_id' : $parentTableId;

        // The base UPDATE query.
        // - Use INNER JON to only select rows that have a match in both parent and child tables
        // - Use DATE_FORMAT() to ensure proper date format because we can be in either string or numeric context.
        // - Use GREATEST() to select the greatest (latest) timestamp from the joined rows from the parent and child table
        // - Use COALESCE() to convert null values to and (old) timestamp because GREATEST considers null to be greater than actual values
        // - Use JSON_SET to only INSERT/UPDATE the relevant key in the json object, not the whole field.
        $queryFormat = 'UPDATE %s p INNER JOIN %s c ON p.%s = c.%s
                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), %s), \'%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.%s", %s)';

        // Set parameter list for GREATEST() - if the child table has relations of its own we need to select the greatest value of "relations_modified_at" and "modified_at".
        $childGreatest = $childHasRelations ? 'c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')' : 'c.modified_at';
        // As above
        $jsonGreatest = $childHasRelations ? sprintf('DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%s\')', self::DB_DATETIME_FORMAT_SQL) : 'c.modified_at';

        $query = sprintf($queryFormat, $parentTable, $childTable, $parentTableId, $childTableId, $childGreatest, self::DB_DATETIME_FORMAT_SQL, $jsonKey, $jsonGreatest);

        // Add WHERE clause to only update rows that have been modified since ":modified_at"
        if ($withWhereClause) {
            $query .= ' WHERE p.modified_at >= :modified_at OR c.modified_at >= :modified_at';

            if ($childHasRelations) {
                $query .= ' OR c.relations_modified_at >= :modified_at';
            }
        }

        return $query;
    }

    /**
     * Get "OnetoMany" query.
     *
     * For a table (parent) that has a toMany relationship to another table (child) where we need to update the "relations_modified_at"
     *  and "relations_modified" fields on the parent with values from the child we need to join the tables and set the values.
     *
     * Example:
     *  UPDATE
     *      playlist p
     *          INNER JOIN (
     *              SELECT
     *                  c.playlist_id, MAX(GREATEST(COALESCE(c.relations_modified_at, '1970-01-01 00:00:00'), c.modified_at)) greatest
     *              FROM
     *                  playlist_slide c
     *              GROUP BY
     *                  c.playlist_id
     *          ) temp
     *          ON
     *              p.id = temp.playlist_id
     *  SET
     *      p.relations_modified_at = temp.greatest,
     *      p.relations_modified = JSON_SET(p.relations_modified, "$.slides", DATE_FORMAT(temp.greatest, '%Y-%m-%d %H:%i:%s'))
     *  WHERE
     *      p.modified_at >= :modified_at OR c.modified_at >= :modified_at OR c.relations_modified_at >= :modified_at
     *
     * Explanation:
     *   Because this is a "to many" relation we need to SELECT the MAX (latest) modified_at timestamp from the child relations. This is done in a temporary table
     *   with SELECT max() and GROUP BY parent id in the child table. This gives us just one child row for each parent row with the latest timestamp.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.relations_modified_at and p.relations_modified values on the parent.
     *    - Because "relations_modified_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
     *    - Because GREATEST() will return a date in numeric format we need to use DATE_FORMAT() to ensure consistent date formats
     *   WHERE either p.modified_at or (child) max_modified_at is greater than a given timestamp
     *    - Because we can't easily get a list of ID's of affected rows as we work up the tree we use the modified_at timestamps as clause in WHERE to limit to only update the rows just modified.
     *
     * @param string $jsonKey
     * @param string $parentTable
     * @param string $childTable
     * @param bool $withWhereClause
     * @return string
     */
    private static function getOneToManyQuery(string $jsonKey, string $parentTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';

        $queryFormat = 'UPDATE %s p
                        INNER JOIN (
                            SELECT c.%s, MAX(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at)) max_modified_at
                            FROM %s c GROUP BY c.%s) temp
                        ON p.id = temp.%s
                        SET p.relations_modified_at = temp.max_modified_at,
                            p.relations_modified = JSON_SET(p.relations_modified, "$.%s", DATE_FORMAT(temp.max_modified_at, \'%s\'))';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $childTable, $parentTableId, $parentTableId, $jsonKey, self::DB_DATETIME_FORMAT_SQL);

        if ($withWhereClause) {
            $query .= ' WHERE p.modified_at >= :modified_at OR temp.max_modified_at >= :modified_at';
        }

        return $query;
    }

    /**
     * Get "many to many" query.
     *
     * For a table (parent) that has a relation to another table (child) through a pivot table where we need to update the "relations_modified_at"
     * and "relations_modified" fields on the parent with values from the child we need to join the tables and set the values.
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
     *      p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, '1970-01-01 00:00:00'), temp.max_modified_at), '%Y-%m-%d %H:%i:%s'),
     *      p.relations_modified = JSON_SET(p.relations_modified, "$.media", max_modified_at)
     *  WHERE
     *      p.modified_at >= :modified_at OR max_modified_at >= :modified_at
     *
     * Explanation:
     *   Because this is a "to many" relation we need to SELECT the MAX (latest) modified_at timestamp from the child relations. This is done in a temporary table
     *   with SELECT max() and GROUP BY parent id in the pivot table. This gives us just one child row for each parent row with the latest timestamp.
     *
     *   This temp table is then joined to the parent table to allow us to SET the p.relations_modified_at and p.relations_modified values on the parent.
     *    - Because "relations_modified_at" can be null and GREATEST() will return null if the given parameter list contains null we need to COALESCE() null values to a date we know to be in the past
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

        $queryFormat = 'UPDATE %s p INNER JOIN (SELECT pivot.%s, max(c.modified_at) as max_modified_at
                           FROM %s pivot INNER JOIN %s c ON pivot.%s=c.id GROUP BY pivot.%s) temp ON p.id = temp.%s
                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), temp.max_modified_at), \'%s\'),
                    p.relations_modified = JSON_SET(p.relations_modified, "$.%s", temp.max_modified_at)';

        $query = sprintf($queryFormat, $parentTable, $parentTableId, $pivotTable, $childTable, $childTableId, $parentTableId, $parentTableId, self::DB_DATETIME_FORMAT_SQL, $jsonKey);
        if ($withWhereClause) {
            $query .= ' WHERE p.modified_at >= :modified_at OR temp.max_modified_at >= :modified_at';
        }

        return $query;
    }
}
