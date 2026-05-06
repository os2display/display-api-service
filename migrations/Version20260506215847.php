<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\DBAL\RRuleType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * Consolidated end-of-2.7 schema.
 *
 * Replaces the 25 historical 2.x migrations (Version20220309093909 …
 * Version20250828084617) with a single migration representing the schema
 * everyone is on after the final 2.7.x release.
 *
 * Includes three columns the 3.0 `Template` entity carries as deprecated,
 * write-only fields — `template.icon`, `template.resources`,
 * `template.description` — so fresh installs and 2.x → 3.0 upgraders end
 * up with the same schema and Doctrine emits a value for each on every
 * INSERT. The deferred drop migration lands in 3.1 together with the
 * matching entity-property removal.
 *
 * Fresh installs run this via `doctrine:migrations:migrate`. 2.x → 3.0
 * upgraders skip execution and run `doctrine:migrations:rollup` instead — see
 * UPGRADE.md step 3.
 *
 * Uses Doctrine's Schema tool API (no raw `addSql`) so the migration is
 * portable across any database Doctrine supports. Going forward, future
 * migrations are required to do the same — enforced by the
 * `NoAddSqlInMigration` PHPStan rule.
 */
final class Version20260506215847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidated end-of-2.7 schema (replaces all 25 historical 2.x migrations).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('feed');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('feed_source_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('configuration', Types::JSON, ['notnull' => false]);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_234044AB9033212A');
        $table->addIndex(['feed_source_id'], 'IDX_234044ABDDAEFFBD');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('feed_source');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('feed_type', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('secrets', Types::JSON, ['notnull' => false]);
        $table->addColumn('supported_feed_output_type', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_9DA80F879033212A');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('interactive_slide_config');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('configuration', Types::JSON, ['notnull' => false]);
        $table->addColumn('implementation_class', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_D30060259033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('media');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('file_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('license', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => '']);
        $table->addColumn('width', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('height', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('size', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('mime_type', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('sha', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_6A2CA10C9033212A');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('playlist');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('is_campaign', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('published_from', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('published_to', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_D782112D9033212A');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('playlist_tenant');
        $table->addColumn('playlist_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['playlist_id', 'tenant_id']);
        $table->addIndex(['playlist_id'], 'IDX_A12FC8516BBD148');
        $table->addIndex(['tenant_id'], 'IDX_A12FC8519033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('playlist_screen_region');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('playlist_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('region_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('weight', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_6869486A9033212A');
        $table->addIndex(['playlist_id'], 'IDX_6869486A6BBD148');
        $table->addIndex(['screen_id'], 'IDX_6869486A41A67722');
        $table->addIndex(['region_id'], 'IDX_6869486A98260155');
        $table->addUniqueIndex(['playlist_id', 'screen_id', 'region_id'], 'unique_playlist_screen_region');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('playlist_slide');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('playlist_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('slide_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('weight', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_D1F3F7219033212A');
        $table->addIndex(['playlist_id'], 'IDX_D1F3F7216BBD148');
        $table->addIndex(['slide_id'], 'IDX_D1F3F721DD5AFB87');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('refresh_tokens');
        $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('refresh_token', Types::STRING, ['length' => 128, 'notnull' => true]);
        $table->addColumn('username', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('valid', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['refresh_token'], 'UNIQ_9BACE7E1C74F2195');
        $this->applyTableOptions($table);

        $table = $schema->createTable('schedule');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('playlist_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('rrule', RRuleType::RRULE, ['length' => 255, 'notnull' => true]);
        $table->addColumn('duration', Types::INTEGER, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_5A3811FB9033212A');
        $table->addIndex(['playlist_id'], 'IDX_5A3811FB6BBD148');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_layout_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('size', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('resolution', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('orientation', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('location', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => '']);
        $table->addColumn('enable_color_scheme_change', Types::BOOLEAN, ['notnull' => false]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_DF4C61309033212A');
        $table->addIndex(['screen_layout_id'], 'IDX_DF4C6130C1ECB8D6');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_campaign');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('campaign_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_636686BD9033212A');
        $table->addIndex(['campaign_id'], 'IDX_636686BDF639F774');
        $table->addIndex(['screen_id'], 'IDX_636686BD41A67722');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_group');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_10C764819033212A');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_group_screen');
        $table->addColumn('screen_group_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['screen_group_id', 'screen_id']);
        $table->addIndex(['screen_group_id'], 'IDX_905749ED82274D27');
        $table->addIndex(['screen_id'], 'IDX_905749ED41A67722');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_group_campaign');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('campaign_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_group_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_1E364E6E9033212A');
        $table->addIndex(['campaign_id'], 'IDX_1E364E6EF639F774');
        $table->addIndex(['screen_group_id'], 'IDX_1E364E6E82274D27');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_layout');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('grid_rows', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('grid_columns', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_layout_tenant');
        $table->addColumn('screen_layout_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['screen_layout_id', 'tenant_id']);
        $table->addIndex(['screen_layout_id'], 'IDX_4B4C32E9C1ECB8D6');
        $table->addIndex(['tenant_id'], 'IDX_4B4C32E99033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_layout_regions');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_layout_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('grid_area', Types::JSON, ['notnull' => true]);
        $table->addColumn('type', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['screen_layout_id'], 'IDX_D80836ADC1ECB8D6');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_layout_regions_tenant');
        $table->addColumn('screen_layout_regions_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['screen_layout_regions_id', 'tenant_id']);
        $table->addIndex(['screen_layout_regions_id'], 'IDX_90A5AF483026129C');
        $table->addIndex(['tenant_id'], 'IDX_90A5AF489033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('screen_user');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('screen_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('username', Types::STRING, ['length' => 180, 'notnull' => true]);
        $table->addColumn('roles', Types::JSON, ['notnull' => true]);
        $table->addColumn('release_timestamp', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('release_version', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('latest_request', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('client_meta', Types::JSON, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['username'], 'UNIQ_8D2D23C6F85E0677');
        $table->addIndex(['tenant_id'], 'IDX_8D2D23C69033212A');
        $table->addUniqueIndex(['screen_id'], 'UNIQ_8D2D23C641A67722');
        $this->applyTableOptions($table);

        $table = $schema->createTable('slide');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('template_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('theme_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('feed_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('duration', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('content', Types::JSON, ['notnull' => false]);
        $table->addColumn('template_options', Types::JSON, ['notnull' => false]);
        $table->addColumn('published_from', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('published_to', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_72EFEE629033212A');
        $table->addIndex(['template_id'], 'IDX_72EFEE625DA0FB8');
        $table->addIndex(['theme_id'], 'IDX_72EFEE6259027487');
        $table->addUniqueIndex(['feed_id'], 'UNIQ_72EFEE6251A5BC03');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('slide_media');
        $table->addColumn('slide_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('media_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['slide_id', 'media_id']);
        $table->addIndex(['slide_id'], 'IDX_EBA5772FDD5AFB87');
        $table->addIndex(['media_id'], 'IDX_EBA5772FEA9FDD75');
        $this->applyTableOptions($table);

        $table = $schema->createTable('template');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('icon', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('resources', Types::JSON, ['notnull' => true]);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('template_tenant');
        $table->addColumn('template_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->setPrimaryKey(['template_id', 'tenant_id']);
        $table->addIndex(['template_id'], 'IDX_45B1CD905DA0FB8');
        $table->addIndex(['tenant_id'], 'IDX_45B1CD909033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('tenant');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('tenant_key', Types::STRING, ['length' => 25, 'notnull' => true]);
        $table->addColumn('fallback_image_url', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['tenant_key'], 'UNIQ_4E59C4623A6F39CD');
        $this->applyTableOptions($table);

        $table = $schema->createTable('theme');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('logo_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('css_styles', Types::TEXT, ['notnull' => true]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('changed', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('relations_checksum', Types::JSON, ['notnull' => true, 'default' => '{}']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['tenant_id'], 'IDX_9775E7089033212A');
        $table->addUniqueIndex(['logo_id'], 'UNIQ_9775E708F98F144A');
        $table->addIndex(['changed'], 'changed_idx');
        $this->applyTableOptions($table);

        $table = $schema->createTable('user');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('provider_id', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('email', Types::STRING, ['length' => 180, 'notnull' => true]);
        $table->addColumn('full_name', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('password', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('provider', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('user_type', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['provider_id'], 'UNIQ_8D93D649A53A8AA');
        $table->addUniqueIndex(['email'], 'UNIQ_8D93D649E7927C74');
        $this->applyTableOptions($table);

        $table = $schema->createTable('user_activation_code');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('code', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('code_expire', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('username', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('roles', Types::JSON, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E88B201577153098');
        $table->addIndex(['tenant_id'], 'IDX_E88B20159033212A');
        $this->applyTableOptions($table);

        $table = $schema->createTable('user_role_tenant');
        $table->addColumn('id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('user_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('tenant_id', UlidType::NAME, ['length' => 16, 'fixed' => true, 'notnull' => true]);
        $table->addColumn('version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('modified_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('created_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('modified_by', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('roles', Types::JSON, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_4C64EC46A76ED395');
        $table->addIndex(['tenant_id'], 'IDX_4C64EC469033212A');
        $table->addUniqueIndex(['user_id', 'tenant_id'], 'user_tenant_unique');
        $this->applyTableOptions($table);

        // Foreign keys (added after all tables exist).
        $schema->getTable('feed')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_234044AB9033212A');
        $schema->getTable('feed')->addForeignKeyConstraint('feed_source', ['feed_source_id'], ['id'], [], 'FK_234044ABDDAEFFBD');
        $schema->getTable('feed_source')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_9DA80F879033212A');
        $schema->getTable('interactive_slide_config')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_D30060259033212A');
        $schema->getTable('media')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_6A2CA10C9033212A');
        $schema->getTable('playlist')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_D782112D9033212A');
        $schema->getTable('playlist_tenant')->addForeignKeyConstraint('playlist', ['playlist_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A12FC8516BBD148');
        $schema->getTable('playlist_tenant')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A12FC8519033212A');
        $schema->getTable('playlist_screen_region')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_6869486A9033212A');
        $schema->getTable('playlist_screen_region')->addForeignKeyConstraint('playlist', ['playlist_id'], ['id'], [], 'FK_6869486A6BBD148');
        $schema->getTable('playlist_screen_region')->addForeignKeyConstraint('screen', ['screen_id'], ['id'], [], 'FK_6869486A41A67722');
        $schema->getTable('playlist_screen_region')->addForeignKeyConstraint('screen_layout_regions', ['region_id'], ['id'], [], 'FK_6869486A98260155');
        $schema->getTable('playlist_slide')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_D1F3F7219033212A');
        $schema->getTable('playlist_slide')->addForeignKeyConstraint('playlist', ['playlist_id'], ['id'], [], 'FK_D1F3F7216BBD148');
        $schema->getTable('playlist_slide')->addForeignKeyConstraint('slide', ['slide_id'], ['id'], [], 'FK_D1F3F721DD5AFB87');
        $schema->getTable('schedule')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_5A3811FB9033212A');
        $schema->getTable('schedule')->addForeignKeyConstraint('playlist', ['playlist_id'], ['id'], [], 'FK_5A3811FB6BBD148');
        $schema->getTable('screen')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_DF4C61309033212A');
        $schema->getTable('screen')->addForeignKeyConstraint('screen_layout', ['screen_layout_id'], ['id'], [], 'FK_DF4C6130C1ECB8D6');
        $schema->getTable('screen_campaign')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_636686BD9033212A');
        $schema->getTable('screen_campaign')->addForeignKeyConstraint('playlist', ['campaign_id'], ['id'], [], 'FK_636686BDF639F774');
        $schema->getTable('screen_campaign')->addForeignKeyConstraint('screen', ['screen_id'], ['id'], [], 'FK_636686BD41A67722');
        $schema->getTable('screen_group')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_10C764819033212A');
        $schema->getTable('screen_group_screen')->addForeignKeyConstraint('screen_group', ['screen_group_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_905749ED82274D27');
        $schema->getTable('screen_group_screen')->addForeignKeyConstraint('screen', ['screen_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_905749ED41A67722');
        $schema->getTable('screen_group_campaign')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_1E364E6E9033212A');
        $schema->getTable('screen_group_campaign')->addForeignKeyConstraint('playlist', ['campaign_id'], ['id'], [], 'FK_1E364E6EF639F774');
        $schema->getTable('screen_group_campaign')->addForeignKeyConstraint('screen_group', ['screen_group_id'], ['id'], [], 'FK_1E364E6E82274D27');
        $schema->getTable('screen_layout_tenant')->addForeignKeyConstraint('screen_layout', ['screen_layout_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_4B4C32E9C1ECB8D6');
        $schema->getTable('screen_layout_tenant')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_4B4C32E99033212A');
        $schema->getTable('screen_layout_regions')->addForeignKeyConstraint('screen_layout', ['screen_layout_id'], ['id'], [], 'FK_D80836ADC1ECB8D6');
        $schema->getTable('screen_layout_regions_tenant')->addForeignKeyConstraint('screen_layout_regions', ['screen_layout_regions_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_90A5AF483026129C');
        $schema->getTable('screen_layout_regions_tenant')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_90A5AF489033212A');
        $schema->getTable('screen_user')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_8D2D23C69033212A');
        $schema->getTable('screen_user')->addForeignKeyConstraint('screen', ['screen_id'], ['id'], [], 'FK_8D2D23C641A67722');
        $schema->getTable('slide')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_72EFEE629033212A');
        $schema->getTable('slide')->addForeignKeyConstraint('template', ['template_id'], ['id'], [], 'FK_72EFEE625DA0FB8');
        $schema->getTable('slide')->addForeignKeyConstraint('theme', ['theme_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_72EFEE6259027487');
        $schema->getTable('slide')->addForeignKeyConstraint('feed', ['feed_id'], ['id'], [], 'FK_72EFEE6251A5BC03');
        $schema->getTable('slide_media')->addForeignKeyConstraint('slide', ['slide_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_EBA5772FDD5AFB87');
        $schema->getTable('slide_media')->addForeignKeyConstraint('media', ['media_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_EBA5772FEA9FDD75');
        $schema->getTable('template_tenant')->addForeignKeyConstraint('template', ['template_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_45B1CD905DA0FB8');
        $schema->getTable('template_tenant')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_45B1CD909033212A');
        $schema->getTable('theme')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_9775E7089033212A');
        $schema->getTable('theme')->addForeignKeyConstraint('media', ['logo_id'], ['id'], [], 'FK_9775E708F98F144A');
        $schema->getTable('user_activation_code')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_E88B20159033212A');
        $schema->getTable('user_role_tenant')->addForeignKeyConstraint('user', ['user_id'], ['id'], [], 'FK_4C64EC46A76ED395');
        $schema->getTable('user_role_tenant')->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_4C64EC469033212A');
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse-dependency order so FKs come down before their referenced tables.
        foreach ([
            'user_role_tenant',
            'user_activation_code',
            'user',
            'theme',
            'tenant',
            'template_tenant',
            'template',
            'slide_media',
            'slide',
            'screen_user',
            'screen_layout_regions_tenant',
            'screen_layout_regions',
            'screen_layout_tenant',
            'screen_layout',
            'screen_group_campaign',
            'screen_group_screen',
            'screen_group',
            'screen_campaign',
            'screen',
            'schedule',
            'refresh_tokens',
            'playlist_slide',
            'playlist_screen_region',
            'playlist_tenant',
            'playlist',
            'media',
            'interactive_slide_config',
            'feed_source',
            'feed',
        ] as $tableName) {
            $schema->dropTable($tableName);
        }
    }

    /**
     * MariaDB-specific table options inherited from the original 2.x migrations
     * (utf8mb4 + InnoDB). Other Doctrine-supported platforms (Postgres, SQLite,
     * SQL Server) ignore these silently — the schema tool emits the correct
     * platform-native DDL either way.
     */
    private function applyTableOptions(Table $table): void
    {
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
        $table->addOption('engine', 'InnoDB');
    }
}
