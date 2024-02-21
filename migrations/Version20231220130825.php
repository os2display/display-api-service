<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\EventListener\RelationsModifiedAtListener;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220130825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON feed (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON feed (modified_at)');
        $this->addSql('ALTER TABLE playlist ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist (modified_at)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_screen_region (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_screen_region (modified_at)');
        $this->addSql('ALTER TABLE playlist_slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_slide (modified_at)');
        $this->addSql('ALTER TABLE screen ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen (modified_at)');
        $this->addSql('ALTER TABLE screen_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_campaign (modified_at)');
        $this->addSql('ALTER TABLE screen_group ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group (modified_at)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group_campaign (modified_at)');
        $this->addSql('ALTER TABLE screen_layout ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE screen_layout_regions ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON slide (modified_at)');
        $this->addSql('ALTER TABLE theme ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON theme (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON theme (modified_at)');

        // Populate newly created 'relations_modified_at' and 'relations_modified' fields with correct data.
        // Use the UPDATE queries defined in the doctrine listener but without a WHERE clause.
        $sqlQueries = self::getUpdateRelationsAtQueries(withWhereClause: false);
        foreach ($sqlQueries as $sqlQuery) {
            $this->addSql($sqlQuery);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist');
        $this->addSql('DROP INDEX modified_at_idx ON playlist');
        $this->addSql('ALTER TABLE playlist DROP relations_modified_at, DROP relations_modified');
        $this->addSql('ALTER TABLE screen_layout DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen');
        $this->addSql('DROP INDEX modified_at_idx ON screen');
        $this->addSql('ALTER TABLE screen DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_slide');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON theme');
        $this->addSql('DROP INDEX modified_at_idx ON theme');
        $this->addSql('ALTER TABLE theme DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON slide');
        $this->addSql('DROP INDEX modified_at_idx ON slide');
        $this->addSql('ALTER TABLE slide DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON feed');
        $this->addSql('DROP INDEX modified_at_idx ON feed');
        $this->addSql('ALTER TABLE feed DROP relations_modified_at, DROP relations_modified');
        $this->addSql('ALTER TABLE screen_layout_regions DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_screen_region');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region DROP relations_modified_at, DROP relations_modified');
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
        $sqlQueries[] = self::getToManyQuery(jsonKey: 'media', parentTable: 'slide', pivotTable: 'slide_media', childTable: 'media', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'theme', parentTable: 'slide', childTable: 'theme', childHasRelations: false, withWhereClause: $withWhereClause);
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
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen_layout', childTable: 'screen_layout_regions', parentTableId: 'id', childTableId: 'screen_layout_id', withWhereClause: $withWhereClause);

        // Screen
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'campaigns', parentTable: 'screen', childTable: 'screen_campaign', parentTableId: 'id', childTableId: 'screen_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'layout', parentTable: 'screen', childTable: 'screen_layout', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToOneQuery(jsonKey: 'regions', parentTable: 'screen', childTable: 'playlist_screen_region', parentTableId: 'id', childTableId: 'screen_id', withWhereClause: $withWhereClause);
        $sqlQueries[] = self::getToManyQuery(jsonKey: 'inScreenGroups', parentTable: 'screen', pivotTable: 'screen_group_screen', childTable: 'screen_group', withWhereClause: $withWhereClause);

        return $sqlQueries;
    }

    /**
     * Get "to one" query.
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
        $jsonGreatest = $childHasRelations ? 'DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\')' : 'c.modified_at';
        $sqlDateFormat = '%Y-%m-%d %H:%i:%s';

        $query = sprintf($queryFormat, $parentTable, $childTable, $parentTableId, $childTableId, $childGreatest, $sqlDateFormat, $jsonKey, $jsonGreatest);

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
     * Get "to many" query.
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
     *  ON
     *      p.id = temp.slide_id
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
