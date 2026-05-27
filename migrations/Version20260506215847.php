<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Consolidated end-of-2.8 schema.
 *
 * Replaces the 25 historical 2.x migrations (Version20220309093909 …
 * Version20250828084617) with a single migration representing the schema
 * everyone is on after the final 2.8.x release.
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
 */
final class Version20260506215847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidated end-of-2.8 schema (replaces all 25 historical 2.x migrations).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE feed (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              feed_source_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              configuration JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_234044AB9033212A (tenant_id),
              INDEX IDX_234044ABDDAEFFBD (feed_source_id),
              INDEX feed_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE feed_source (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              feed_type VARCHAR(255) NOT NULL,
              secrets JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              supported_feed_output_type VARCHAR(255) NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_9DA80F879033212A (tenant_id),
              INDEX feed_source_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE interactive_slide_config (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              configuration JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              implementation_class VARCHAR(255) NOT NULL,
              INDEX IDX_D30060259033212A (tenant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE media (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              file_path VARCHAR(255) DEFAULT NULL,
              license VARCHAR(255) DEFAULT '',
              width INT DEFAULT 0 NOT NULL,
              height INT DEFAULT 0 NOT NULL,
              size INT DEFAULT 0 NOT NULL,
              mime_type VARCHAR(255) DEFAULT '' NOT NULL,
              sha VARCHAR(255) DEFAULT '' NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_6A2CA10C9033212A (tenant_id),
              INDEX media_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE playlist (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              is_campaign TINYINT(1) NOT NULL,
              published_from DATETIME DEFAULT NULL,
              published_to DATETIME DEFAULT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_D782112D9033212A (tenant_id),
              INDEX playlist_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE playlist_tenant (
              playlist_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_A12FC8516BBD148 (playlist_id),
              INDEX IDX_A12FC8519033212A (tenant_id),
              PRIMARY KEY(playlist_id, tenant_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE playlist_screen_region (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              playlist_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              region_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              weight INT DEFAULT 0 NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_6869486A9033212A (tenant_id),
              INDEX IDX_6869486A6BBD148 (playlist_id),
              INDEX IDX_6869486A41A67722 (screen_id),
              INDEX IDX_6869486A98260155 (region_id),
              INDEX playlist_screen_region_changed_idx (changed),
              UNIQUE INDEX unique_playlist_screen_region (
                playlist_id, screen_id, region_id
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE playlist_slide (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              playlist_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              slide_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              weight INT DEFAULT 0 NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_D1F3F7219033212A (tenant_id),
              INDEX IDX_D1F3F7216BBD148 (playlist_id),
              INDEX IDX_D1F3F721DD5AFB87 (slide_id),
              INDEX playlist_slide_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (
              id INT AUTO_INCREMENT NOT NULL,
              refresh_token VARCHAR(128) NOT NULL,
              username VARCHAR(255) NOT NULL,
              valid DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE schedule (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              playlist_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              rrule VARCHAR(255) NOT NULL COMMENT '(DC2Type:rrule)',
              duration INT NOT NULL,
              INDEX IDX_5A3811FB9033212A (tenant_id),
              INDEX IDX_5A3811FB6BBD148 (playlist_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_layout_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              size INT DEFAULT 0 NOT NULL,
              resolution VARCHAR(255) DEFAULT '' NOT NULL,
              orientation VARCHAR(255) DEFAULT '' NOT NULL,
              location VARCHAR(255) DEFAULT '',
              enable_color_scheme_change TINYINT(1) DEFAULT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_DF4C61309033212A (tenant_id),
              INDEX IDX_DF4C6130C1ECB8D6 (screen_layout_id),
              INDEX screen_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_campaign (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              campaign_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_636686BD9033212A (tenant_id),
              INDEX IDX_636686BDF639F774 (campaign_id),
              INDEX IDX_636686BD41A67722 (screen_id),
              INDEX screen_campaign_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_group (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_10C764819033212A (tenant_id),
              INDEX screen_group_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_group_screen (
              screen_group_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_905749ED82274D27 (screen_group_id),
              INDEX IDX_905749ED41A67722 (screen_id),
              PRIMARY KEY(screen_group_id, screen_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_group_campaign (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              campaign_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_group_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_1E364E6E9033212A (tenant_id),
              INDEX IDX_1E364E6EF639F774 (campaign_id),
              INDEX IDX_1E364E6E82274D27 (screen_group_id),
              INDEX screen_group_campaign_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_layout (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              grid_rows INT DEFAULT 0 NOT NULL,
              grid_columns INT DEFAULT 0 NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX screen_layout_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_layout_tenant (
              screen_layout_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_4B4C32E9C1ECB8D6 (screen_layout_id),
              INDEX IDX_4B4C32E99033212A (tenant_id),
              PRIMARY KEY(screen_layout_id, tenant_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_layout_regions (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_layout_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              grid_area JSON NOT NULL COMMENT '(DC2Type:json)',
              type VARCHAR(255) DEFAULT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_D80836ADC1ECB8D6 (screen_layout_id),
              INDEX screen_layout_regions_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_layout_regions_tenant (
              screen_layout_regions_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_90A5AF483026129C (screen_layout_regions_id),
              INDEX IDX_90A5AF489033212A (tenant_id),
              PRIMARY KEY(
                screen_layout_regions_id, tenant_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE screen_user (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              screen_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              username VARCHAR(180) NOT NULL,
              roles JSON NOT NULL COMMENT '(DC2Type:json)',
              release_timestamp INT DEFAULT NULL,
              release_version VARCHAR(255) DEFAULT NULL,
              latest_request DATETIME DEFAULT NULL,
              client_meta JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              UNIQUE INDEX UNIQ_8D2D23C6F85E0677 (username),
              INDEX IDX_8D2D23C69033212A (tenant_id),
              UNIQUE INDEX UNIQ_8D2D23C641A67722 (screen_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE slide (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              template_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              theme_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)',
              feed_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              duration INT DEFAULT NULL,
              content JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              template_options JSON DEFAULT NULL COMMENT '(DC2Type:json)',
              published_from DATETIME DEFAULT NULL,
              published_to DATETIME DEFAULT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_72EFEE629033212A (tenant_id),
              INDEX IDX_72EFEE625DA0FB8 (template_id),
              INDEX IDX_72EFEE6259027487 (theme_id),
              UNIQUE INDEX UNIQ_72EFEE6251A5BC03 (feed_id),
              INDEX slide_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE slide_media (
              slide_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              media_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_EBA5772FDD5AFB87 (slide_id),
              INDEX IDX_EBA5772FEA9FDD75 (media_id),
              PRIMARY KEY(slide_id, media_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE template (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              icon VARCHAR(255) DEFAULT '' NOT NULL,
              resources JSON NOT NULL COMMENT '(DC2Type:json)',
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX template_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE template_tenant (
              template_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              INDEX IDX_45B1CD905DA0FB8 (template_id),
              INDEX IDX_45B1CD909033212A (tenant_id),
              PRIMARY KEY(template_id, tenant_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tenant (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              tenant_key VARCHAR(25) NOT NULL,
              fallback_image_url VARCHAR(255) DEFAULT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              UNIQUE INDEX UNIQ_4E59C4623A6F39CD (tenant_key),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE theme (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              logo_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              css_styles LONGTEXT NOT NULL,
              title VARCHAR(255) DEFAULT '' NOT NULL,
              description VARCHAR(255) DEFAULT '' NOT NULL,
              changed TINYINT(1) NOT NULL,
              relations_checksum JSON DEFAULT '{}' NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_9775E7089033212A (tenant_id),
              UNIQUE INDEX UNIQ_9775E708F98F144A (logo_id),
              INDEX theme_changed_idx (changed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              provider_id VARCHAR(255) NOT NULL,
              email VARCHAR(180) NOT NULL,
              full_name VARCHAR(255) NOT NULL,
              password VARCHAR(255) NOT NULL,
              provider VARCHAR(255) NOT NULL,
              user_type VARCHAR(255) NOT NULL,
              UNIQUE INDEX UNIQ_8D93D649A53A8AA (provider_id),
              UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_activation_code (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              code VARCHAR(255) NOT NULL,
              code_expire DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              username VARCHAR(255) NOT NULL,
              roles JSON NOT NULL COMMENT '(DC2Type:json)',
              UNIQUE INDEX UNIQ_E88B201577153098 (code),
              INDEX IDX_E88B20159033212A (tenant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_role_tenant (
              id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              user_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:ulid)',
              version INT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              modified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_by VARCHAR(255) DEFAULT '' NOT NULL,
              modified_by VARCHAR(255) DEFAULT '' NOT NULL,
              roles JSON NOT NULL COMMENT '(DC2Type:json)',
              INDEX IDX_4C64EC46A76ED395 (user_id),
              INDEX IDX_4C64EC469033212A (tenant_id),
              UNIQUE INDEX user_tenant_unique (user_id, tenant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              feed
            ADD
              CONSTRAINT FK_234044AB9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              feed
            ADD
              CONSTRAINT FK_234044ABDDAEFFBD FOREIGN KEY (feed_source_id) REFERENCES feed_source (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              feed_source
            ADD
              CONSTRAINT FK_9DA80F879033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interactive_slide_config
            ADD
              CONSTRAINT FK_D30060259033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              media
            ADD
              CONSTRAINT FK_6A2CA10C9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist
            ADD
              CONSTRAINT FK_D782112D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_tenant
            ADD
              CONSTRAINT FK_A12FC8516BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_tenant
            ADD
              CONSTRAINT FK_A12FC8519033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_screen_region
            ADD
              CONSTRAINT FK_6869486A9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_screen_region
            ADD
              CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_screen_region
            ADD
              CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_screen_region
            ADD
              CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_slide
            ADD
              CONSTRAINT FK_D1F3F7219033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_slide
            ADD
              CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              playlist_slide
            ADD
              CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              schedule
            ADD
              CONSTRAINT FK_5A3811FB9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              schedule
            ADD
              CONSTRAINT FK_5A3811FB6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen
            ADD
              CONSTRAINT FK_DF4C61309033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen
            ADD
              CONSTRAINT FK_DF4C6130C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_campaign
            ADD
              CONSTRAINT FK_636686BD9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_campaign
            ADD
              CONSTRAINT FK_636686BDF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_campaign
            ADD
              CONSTRAINT FK_636686BD41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group
            ADD
              CONSTRAINT FK_10C764819033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group_screen
            ADD
              CONSTRAINT FK_905749ED82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group_screen
            ADD
              CONSTRAINT FK_905749ED41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group_campaign
            ADD
              CONSTRAINT FK_1E364E6E9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group_campaign
            ADD
              CONSTRAINT FK_1E364E6EF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_group_campaign
            ADD
              CONSTRAINT FK_1E364E6E82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_layout_tenant
            ADD
              CONSTRAINT FK_4B4C32E9C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_layout_tenant
            ADD
              CONSTRAINT FK_4B4C32E99033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_layout_regions
            ADD
              CONSTRAINT FK_D80836ADC1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_layout_regions_tenant
            ADD
              CONSTRAINT FK_90A5AF483026129C FOREIGN KEY (screen_layout_regions_id) REFERENCES screen_layout_regions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_layout_regions_tenant
            ADD
              CONSTRAINT FK_90A5AF489033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_user
            ADD
              CONSTRAINT FK_8D2D23C69033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              screen_user
            ADD
              CONSTRAINT FK_8D2D23C641A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide
            ADD
              CONSTRAINT FK_72EFEE629033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide
            ADD
              CONSTRAINT FK_72EFEE625DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide
            ADD
              CONSTRAINT FK_72EFEE6259027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide
            ADD
              CONSTRAINT FK_72EFEE6251A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide_media
            ADD
              CONSTRAINT FK_EBA5772FDD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              slide_media
            ADD
              CONSTRAINT FK_EBA5772FEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              template_tenant
            ADD
              CONSTRAINT FK_45B1CD905DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              template_tenant
            ADD
              CONSTRAINT FK_45B1CD909033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              theme
            ADD
              CONSTRAINT FK_9775E7089033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              theme
            ADD
              CONSTRAINT FK_9775E708F98F144A FOREIGN KEY (logo_id) REFERENCES media (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_activation_code
            ADD
              CONSTRAINT FK_E88B20159033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_role_tenant
            ADD
              CONSTRAINT FK_4C64EC46A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_role_tenant
            ADD
              CONSTRAINT FK_4C64EC469033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feed DROP FOREIGN KEY FK_234044AB9033212A');
        $this->addSql('ALTER TABLE feed DROP FOREIGN KEY FK_234044ABDDAEFFBD');
        $this->addSql('ALTER TABLE feed_source DROP FOREIGN KEY FK_9DA80F879033212A');
        $this->addSql('ALTER TABLE interactive_slide_config DROP FOREIGN KEY FK_D30060259033212A');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C9033212A');
        $this->addSql('ALTER TABLE playlist DROP FOREIGN KEY FK_D782112D9033212A');
        $this->addSql('ALTER TABLE playlist_tenant DROP FOREIGN KEY FK_A12FC8516BBD148');
        $this->addSql('ALTER TABLE playlist_tenant DROP FOREIGN KEY FK_A12FC8519033212A');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A9033212A');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A6BBD148');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A41A67722');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A98260155');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7219033212A');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB9033212A');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB6BBD148');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C61309033212A');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C6130C1ECB8D6');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BD9033212A');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BDF639F774');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BD41A67722');
        $this->addSql('ALTER TABLE screen_group DROP FOREIGN KEY FK_10C764819033212A');
        $this->addSql('ALTER TABLE screen_group_screen DROP FOREIGN KEY FK_905749ED82274D27');
        $this->addSql('ALTER TABLE screen_group_screen DROP FOREIGN KEY FK_905749ED41A67722');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6E9033212A');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6EF639F774');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6E82274D27');
        $this->addSql('ALTER TABLE screen_layout_tenant DROP FOREIGN KEY FK_4B4C32E9C1ECB8D6');
        $this->addSql('ALTER TABLE screen_layout_tenant DROP FOREIGN KEY FK_4B4C32E99033212A');
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836ADC1ECB8D6');
        $this->addSql('ALTER TABLE screen_layout_regions_tenant DROP FOREIGN KEY FK_90A5AF483026129C');
        $this->addSql('ALTER TABLE screen_layout_regions_tenant DROP FOREIGN KEY FK_90A5AF489033212A');
        $this->addSql('ALTER TABLE screen_user DROP FOREIGN KEY FK_8D2D23C69033212A');
        $this->addSql('ALTER TABLE screen_user DROP FOREIGN KEY FK_8D2D23C641A67722');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE629033212A');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE625DA0FB8');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6259027487');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6251A5BC03');
        $this->addSql('ALTER TABLE slide_media DROP FOREIGN KEY FK_EBA5772FDD5AFB87');
        $this->addSql('ALTER TABLE slide_media DROP FOREIGN KEY FK_EBA5772FEA9FDD75');
        $this->addSql('ALTER TABLE template_tenant DROP FOREIGN KEY FK_45B1CD905DA0FB8');
        $this->addSql('ALTER TABLE template_tenant DROP FOREIGN KEY FK_45B1CD909033212A');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E7089033212A');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708F98F144A');
        $this->addSql('ALTER TABLE user_activation_code DROP FOREIGN KEY FK_E88B20159033212A');
        $this->addSql('ALTER TABLE user_role_tenant DROP FOREIGN KEY FK_4C64EC46A76ED395');
        $this->addSql('ALTER TABLE user_role_tenant DROP FOREIGN KEY FK_4C64EC469033212A');
        $this->addSql('DROP TABLE feed');
        $this->addSql('DROP TABLE feed_source');
        $this->addSql('DROP TABLE interactive_slide_config');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_tenant');
        $this->addSql('DROP TABLE playlist_screen_region');
        $this->addSql('DROP TABLE playlist_slide');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE screen');
        $this->addSql('DROP TABLE screen_campaign');
        $this->addSql('DROP TABLE screen_group');
        $this->addSql('DROP TABLE screen_group_screen');
        $this->addSql('DROP TABLE screen_group_campaign');
        $this->addSql('DROP TABLE screen_layout');
        $this->addSql('DROP TABLE screen_layout_tenant');
        $this->addSql('DROP TABLE screen_layout_regions');
        $this->addSql('DROP TABLE screen_layout_regions_tenant');
        $this->addSql('DROP TABLE screen_user');
        $this->addSql('DROP TABLE slide');
        $this->addSql('DROP TABLE slide_media');
        $this->addSql('DROP TABLE template');
        $this->addSql('DROP TABLE template_tenant');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_activation_code');
        $this->addSql('DROP TABLE user_role_tenant');
    }
}
