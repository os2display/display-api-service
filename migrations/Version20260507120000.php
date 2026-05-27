<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Portability prep: give the 15 `changed_idx` indexes table-scoped names so
 * they don't collide on platforms (such as Postgres) that scope index names
 * schema-wide rather than per-table.
 *
 * No-op for MariaDB at runtime. Landing on 2.8 means the consolidated 3.0
 * migration can emit the portable shape from the start, keeping
 * `migrations:rollup` honest for 2.x → 3.0 upgraders. The reserved-keyword
 * problem with the `user` table is handled separately by backtick-quoting
 * the identifier in the entity attribute (no rename needed).
 */
final class Version20260507120000 extends AbstractMigration
{
    private const CHANGED_IDX_TABLES = [
        'feed',
        'feed_source',
        'media',
        'playlist',
        'playlist_screen_region',
        'playlist_slide',
        'screen',
        'screen_campaign',
        'screen_group',
        'screen_group_campaign',
        'screen_layout',
        'screen_layout_regions',
        'slide',
        'template',
        'theme',
    ];

    public function getDescription(): string
    {
        return 'Uniquify the 15 `changed_idx` indexes (table-scoped names) for cross-platform portability.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CHANGED_IDX_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE `%s` RENAME INDEX `changed_idx` TO `%s_changed_idx`', $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::CHANGED_IDX_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE `%s` RENAME INDEX `%s_changed_idx` TO `changed_idx`', $table, $table));
        }
    }
}
