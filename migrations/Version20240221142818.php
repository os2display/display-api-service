<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240221142818 extends AbstractMigration
{
    private const CHECKSUM_TABLES = ['feed_source', 'feed', 'slide', 'media', 'theme', 'template', 'playlist_slide',
        'playlist', 'screen_campaign', 'screen', 'screen_group_campaign', 'screen_group',
        'playlist_screen_region', 'screen_layout_regions', 'screen_layout'];

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX relations_modified_at_idx ON feed');
        $this->addSql('DROP INDEX modified_at_idx ON feed');
        $this->addSql('ALTER TABLE feed ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON feed (changed)');
        $this->addSql('ALTER TABLE feed_source ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON feed_source (changed)');
        $this->addSql('ALTER TABLE media ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON media (changed)');
        $this->addSql('DROP INDEX modified_at_idx ON playlist');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist');
        $this->addSql('ALTER TABLE playlist ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_screen_region');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist_screen_region (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_slide');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist_slide (changed)');
        $this->addSql('ALTER TABLE schedule ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen');
        $this->addSql('DROP INDEX modified_at_idx ON screen');
        $this->addSql('ALTER TABLE screen ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_campaign (changed)');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_group (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_group_campaign (changed)');
        $this->addSql('ALTER TABLE screen_layout ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_layout (changed)');
        $this->addSql('ALTER TABLE screen_layout_regions ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_layout_regions (changed)');
        $this->addSql('ALTER TABLE screen_user ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX modified_at_idx ON slide');
        $this->addSql('DROP INDEX relations_modified_at_idx ON slide');
        $this->addSql('ALTER TABLE slide ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON slide (changed)');
        $this->addSql('ALTER TABLE template ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON template (changed)');
        $this->addSql('ALTER TABLE tenant ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE theme ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON theme (changed)');
        $this->addSql('ALTER TABLE user ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user_role_tenant ADD version INT DEFAULT 1 NOT NULL');

        // Populate newly created 'changed' and 'relations_modified' fields with correct data.
        // Copy of the UPDATE queries defined in the doctrine listener but without a WHERE clause.
        // Duplication ensures that the queries match the schema for this migration even if the schema and
        // queries are refactored.
        $sqlQueries = self::getUpdateRelationsAtQueries(withWhereClause: false);
        foreach ($sqlQueries as $sqlQuery) {
            $this->addSql($sqlQuery);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX changed_idx ON template');
        $this->addSql('ALTER TABLE template DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON slide');
        $this->addSql('ALTER TABLE slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON slide (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON slide (relations_modified_at)');
        $this->addSql('ALTER TABLE user DROP version');
        $this->addSql('ALTER TABLE schedule DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group (relations_modified_at)');
        $this->addSql('ALTER TABLE tenant DROP version');
        $this->addSql('DROP INDEX changed_idx ON feed_source');
        $this->addSql('ALTER TABLE feed_source DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group_campaign (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON screen_layout_regions');
        $this->addSql('ALTER TABLE screen_layout_regions ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX changed_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_slide (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON theme');
        $this->addSql('ALTER TABLE theme DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON playlist');
        $this->addSql('ALTER TABLE playlist ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist (relations_modified_at)');
        $this->addSql('DROP INDEX changed_idx ON feed');
        $this->addSql('ALTER TABLE feed ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON feed (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON feed (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_screen_region (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_screen_region (modified_at)');
        $this->addSql('ALTER TABLE screen_user DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_campaign (modified_at)');
        $this->addSql('ALTER TABLE user_role_tenant DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_layout');
        $this->addSql('ALTER TABLE screen_layout ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX changed_idx ON media');
        $this->addSql('ALTER TABLE media DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON screen');
        $this->addSql('ALTER TABLE screen ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen (modified_at)');
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
     * @param string $jsonKey
     * @param string $parentTable
     * @param string $childTable
     * @param string|null $parentTableId
     * @param string $childTableId
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
