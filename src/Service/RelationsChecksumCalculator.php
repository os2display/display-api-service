<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Service for calculating and propagating relation checksums through the entity tree.
 *
 * Generates and executes raw SQL UPDATE statements that propagate SHA1 checksums
 * from child entities up through the relationship tree (e.g. media -> slide ->
 * playlist_slide -> playlist -> screen).
 *
 * Extracted from RelationsChecksumListener to allow reuse in console commands
 * and other contexts outside Doctrine lifecycle events.
 */
class RelationsChecksumCalculator
{
    public const array CHECKSUM_TABLES = ['feed_source', 'feed', 'slide', 'media', 'theme', 'template', 'playlist_slide',
        'playlist', 'screen_campaign', 'screen', 'screen_group_campaign', 'screen_group',
        'playlist_screen_region', 'screen_layout_regions', 'screen_layout'];

    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Execute all checksum propagation queries in a transaction.
     *
     * @param bool $withWhereClause limit updates to rows where changed = 1
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function execute(bool $withWhereClause = true): void
    {
        $sqlQueries = $this->getUpdateRelationsAtQueries(withWhereClause: $withWhereClause);

        $this->connection->beginTransaction();

        try {
            foreach ($sqlQueries as $sqlQuery) {
                $stm = $this->connection->prepare($sqlQuery);
                $stm->executeStatement();
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
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
    public function getUpdateRelationsAtQueries(bool $withWhereClause = true): array
    {
        // Set SQL update queries for the "relations checksum" fields on the parent (p), child (c) relationships up through the entity tree
        $sqlQueries = [];

        // Feed
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'feedSource', parentTable: 'feed', childTable: 'feed_source', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'slide', parentTable: 'feed', childTable: 'slide', parentTableId: 'id', childTableId: 'feed_id', withWhereClause: $withWhereClause);

        // Slide
        $sqlQueries[] = $this->getManyToManyQuery(jsonKey: 'media', parentTable: 'slide', pivotTable: 'slide_media', childTable: 'media', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'theme', parentTable: 'slide', childTable: 'theme', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'templateInfo', parentTable: 'slide', childTable: 'template', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'feed', parentTable: 'slide', childTable: 'feed', withWhereClause: $withWhereClause);

        // PlaylistSlide
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'slide', parentTable: 'playlist_slide', childTable: 'slide', withWhereClause: $withWhereClause);

        // Playlist
        $sqlQueries[] = $this->getOneToManyQuery(jsonKey: 'slides', parentTable: 'playlist', childTable: 'playlist_slide', withWhereClause: $withWhereClause);

        // ScreenCampaign
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'screen', parentTable: 'screen_campaign', childTable: 'screen', withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - campaign
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'campaign', parentTable: 'screen_group_campaign', childTable: 'playlist', parentTableId: 'campaign_id', withWhereClause: $withWhereClause);

        // ScreenGroup
        $sqlQueries[] = $this->getManyToManyQuery(jsonKey: 'screens', parentTable: 'screen_group', pivotTable: 'screen_group_screen', childTable: 'screen', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getOneToManyQuery(jsonKey: 'screenGroupCampaigns', parentTable: 'screen_group', childTable: 'screen_group_campaign', withWhereClause: $withWhereClause);

        // ScreenGroupCampaign - screenGroup
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'screenGroup', parentTable: 'screen_group_campaign', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // PlaylistScreenRegion
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'playlist', parentTable: 'playlist_screen_region', childTable: 'playlist', withWhereClause: $withWhereClause);

        // ScreenLayoutRegions
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'regions', parentTable: 'screen_layout_regions', childTable: 'playlist_screen_region', parentTableId: 'id', childTableId: 'region_id', withWhereClause: $withWhereClause);

        // ScreenLayout
        $sqlQueries[] = $this->getOneToManyQuery(jsonKey: 'regions', parentTable: 'screen_layout', childTable: 'screen_layout_regions', withWhereClause: $withWhereClause);

        // Screen
        $sqlQueries[] = $this->getOneToManyQuery(jsonKey: 'campaigns', parentTable: 'screen', childTable: 'screen_campaign', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getToOneQuery(jsonKey: 'layout', parentTable: 'screen', childTable: 'screen_layout', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getOneToManyQuery(jsonKey: 'regions', parentTable: 'screen', childTable: 'playlist_screen_region', withWhereClause: $withWhereClause);
        $sqlQueries[] = $this->getManyToManyQuery(jsonKey: 'inScreenGroups', parentTable: 'screen', pivotTable: 'screen_group_screen', childTable: 'screen_group', withWhereClause: $withWhereClause);

        // Add reset 'changed' fields queries
        $sqlQueries = array_merge($sqlQueries, $this->getResetChangedQueries());

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
     * @param string|null $parentTableId
     */
    private function getToOneQuery(string $jsonKey, string $parentTable, string $childTable, ?string $parentTableId = null, string $childTableId = 'id', bool $withWhereClause = true): string
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
     *          CAST(GROUP_CONCAT(DISTINCT c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
     *          SHA1(GROUP_CONCAT(c.id, c.version, c.relations_checksum)) checksum
     *      FROM
     *          playlist_slide c
     *      GROUP BY
     *          c.playlist_id
     *      ) temp ON p.id = temp.playlist_id
     *  SET p.changed = 1,
     *      p.relations_checksum = JSON_SET(p.relations_checksum, "$.slides", temp.checksum)
     *  WHERE p.changed = 1 OR temp.changed = 1
     */
    private function getOneToManyQuery(string $jsonKey, string $parentTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';

        $queryFormat = '
            UPDATE
                %s p
                INNER JOIN (
                    SELECT
                        c.%s,
                        CAST(GROUP_CONCAT(DISTINCT c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
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
     * Example:
     *  UPDATE
     *      slide p
     *  INNER JOIN (
     *      SELECT
     *          pivot.slide_id,
     *          CAST(GROUP_CONCAT(DISTINCT c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
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
     */
    private function getManyToManyQuery(string $jsonKey, string $parentTable, string $pivotTable, string $childTable, bool $withWhereClause = true): string
    {
        $parentTableId = $parentTable.'_id';
        $childTableId = $childTable.'_id';

        $queryFormat = '
            UPDATE
                %s p
                INNER JOIN (
                    SELECT
                        pivot.%s,
                        CAST(GROUP_CONCAT(DISTINCT c.changed SEPARATOR "") > 0 AS UNSIGNED) changed,
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
     * @return string[]
     */
    private function getResetChangedQueries(): array
    {
        $queries = [];
        foreach (self::CHECKSUM_TABLES as $table) {
            $queries[] = sprintf('UPDATE %s SET changed = 0 WHERE changed = 1', $table);
        }

        return $queries;
    }
}
