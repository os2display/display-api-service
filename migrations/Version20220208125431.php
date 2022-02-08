<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220208125431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feed (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', feed_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, configuration LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_234044AB9033212A (tenant_id), INDEX IDX_234044ABDDAEFFBD (feed_source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_source (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, feed_type VARCHAR(255) NOT NULL, secrets LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', configuration LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_9DA80F879033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE media (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, file_path VARCHAR(255) DEFAULT NULL, license VARCHAR(255) DEFAULT \'\', width INT DEFAULT 0 NOT NULL, height INT DEFAULT 0 NOT NULL, size INT DEFAULT 0 NOT NULL, mime_type VARCHAR(255) DEFAULT \'\' NOT NULL, sha VARCHAR(255) DEFAULT \'\' NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_6A2CA10C9033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, is_campaign TINYINT(1) NOT NULL, published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_D782112D9033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_screen_region (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', region_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, weight INT DEFAULT 0 NOT NULL, INDEX IDX_6869486A9033212A (tenant_id), INDEX IDX_6869486A6BBD148 (playlist_id), INDEX IDX_6869486A41A67722 (screen_id), INDEX IDX_6869486A98260155 (region_id), UNIQUE INDEX unique_idx (playlist_id, screen_id, region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_slide (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', slide_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, weight INT DEFAULT 0 NOT NULL, INDEX IDX_D1F3F7219033212A (tenant_id), INDEX IDX_D1F3F7216BBD148 (playlist_id), INDEX IDX_D1F3F721DD5AFB87 (slide_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', rrule VARCHAR(255) NOT NULL COMMENT \'(DC2Type:rrule)\', duration INT NOT NULL, INDEX IDX_5A3811FB6BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_layout_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, size INT DEFAULT 0 NOT NULL, resolution_width INT DEFAULT 0 NOT NULL, resolution_height INT DEFAULT 0 NOT NULL, location VARCHAR(255) DEFAULT \'\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_DF4C61309033212A (tenant_id), INDEX IDX_DF4C6130C1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_campaign (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_636686BD9033212A (tenant_id), INDEX IDX_636686BDF639F774 (campaign_id), INDEX IDX_636686BD41A67722 (screen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_10C764819033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group_screen (screen_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_905749ED82274D27 (screen_group_id), INDEX IDX_905749ED41A67722 (screen_id), PRIMARY KEY(screen_group_id, screen_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group_campaign (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_1E364E6E9033212A (tenant_id), INDEX IDX_1E364E6EF639F774 (campaign_id), INDEX IDX_1E364E6E82274D27 (screen_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, grid_rows INT DEFAULT 0 NOT NULL, grid_columns INT DEFAULT 0 NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_4BF6E94B9033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout_regions (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_layout_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, grid_area LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_D80836AD9033212A (tenant_id), INDEX IDX_D80836ADC1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_user (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, username VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_8D2D23C6F85E0677 (username), INDEX IDX_8D2D23C69033212A (tenant_id), UNIQUE INDEX UNIQ_8D2D23C641A67722 (screen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slide (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', template_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', theme_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', feed_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, template_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', duration INT DEFAULT NULL, content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_72EFEE629033212A (tenant_id), INDEX IDX_72EFEE625DA0FB8 (template_id), INDEX IDX_72EFEE6259027487 (theme_id), UNIQUE INDEX UNIQ_72EFEE6251A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slide_media (slide_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', media_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_EBA5772FDD5AFB87 (slide_id), INDEX IDX_EBA5772FEA9FDD75 (media_id), PRIMARY KEY(slide_id, media_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', icon VARCHAR(255) DEFAULT \'\' NOT NULL, resources LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tenant (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, css_styles LONGTEXT NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, INDEX IDX_9775E7089033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', email VARCHAR(180) NOT NULL, full_name VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_tenant (user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_2B0BDF5FA76ED395 (user_id), INDEX IDX_2B0BDF5F9033212A (tenant_id), PRIMARY KEY(user_id, tenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT FK_234044AB9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT FK_234044ABDDAEFFBD FOREIGN KEY (feed_source_id) REFERENCES feed_source (id)');
        $this->addSql('ALTER TABLE feed_source ADD CONSTRAINT FK_9DA80F879033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE playlist ADD CONSTRAINT FK_D782112D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7219033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id)');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen ADD CONSTRAINT FK_DF4C61309033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen ADD CONSTRAINT FK_DF4C6130C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE screen_campaign ADD CONSTRAINT FK_636686BD9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_campaign ADD CONSTRAINT FK_636686BDF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen_campaign ADD CONSTRAINT FK_636686BD41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE screen_group ADD CONSTRAINT FK_10C764819033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_group_screen ADD CONSTRAINT FK_905749ED82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_group_screen ADD CONSTRAINT FK_905749ED41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6E9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6EF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6E82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id)');
        $this->addSql('ALTER TABLE screen_layout ADD CONSTRAINT FK_4BF6E94B9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_layout_regions ADD CONSTRAINT FK_D80836AD9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_layout_regions ADD CONSTRAINT FK_D80836ADC1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE screen_user ADD CONSTRAINT FK_8D2D23C69033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE screen_user ADD CONSTRAINT FK_8D2D23C641A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE629033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE625DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6259027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6251A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)');
        $this->addSql('ALTER TABLE slide_media ADD CONSTRAINT FK_EBA5772FDD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slide_media ADD CONSTRAINT FK_EBA5772FEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E7089033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE user_tenant ADD CONSTRAINT FK_2B0BDF5FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_tenant ADD CONSTRAINT FK_2B0BDF5F9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6251A5BC03');
        $this->addSql('ALTER TABLE feed DROP FOREIGN KEY FK_234044ABDDAEFFBD');
        $this->addSql('ALTER TABLE slide_media DROP FOREIGN KEY FK_EBA5772FEA9FDD75');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A6BBD148');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB6BBD148');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BDF639F774');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6EF639F774');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A41A67722');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BD41A67722');
        $this->addSql('ALTER TABLE screen_group_screen DROP FOREIGN KEY FK_905749ED41A67722');
        $this->addSql('ALTER TABLE screen_user DROP FOREIGN KEY FK_8D2D23C641A67722');
        $this->addSql('ALTER TABLE screen_group_screen DROP FOREIGN KEY FK_905749ED82274D27');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6E82274D27');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C6130C1ECB8D6');
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836ADC1ECB8D6');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A98260155');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE slide_media DROP FOREIGN KEY FK_EBA5772FDD5AFB87');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE625DA0FB8');
        $this->addSql('ALTER TABLE feed DROP FOREIGN KEY FK_234044AB9033212A');
        $this->addSql('ALTER TABLE feed_source DROP FOREIGN KEY FK_9DA80F879033212A');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C9033212A');
        $this->addSql('ALTER TABLE playlist DROP FOREIGN KEY FK_D782112D9033212A');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A9033212A');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7219033212A');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C61309033212A');
        $this->addSql('ALTER TABLE screen_campaign DROP FOREIGN KEY FK_636686BD9033212A');
        $this->addSql('ALTER TABLE screen_group DROP FOREIGN KEY FK_10C764819033212A');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6E9033212A');
        $this->addSql('ALTER TABLE screen_layout DROP FOREIGN KEY FK_4BF6E94B9033212A');
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836AD9033212A');
        $this->addSql('ALTER TABLE screen_user DROP FOREIGN KEY FK_8D2D23C69033212A');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE629033212A');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E7089033212A');
        $this->addSql('ALTER TABLE user_tenant DROP FOREIGN KEY FK_2B0BDF5F9033212A');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6259027487');
        $this->addSql('ALTER TABLE user_tenant DROP FOREIGN KEY FK_2B0BDF5FA76ED395');
        $this->addSql('DROP TABLE feed');
        $this->addSql('DROP TABLE feed_source');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_screen_region');
        $this->addSql('DROP TABLE playlist_slide');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE screen');
        $this->addSql('DROP TABLE screen_campaign');
        $this->addSql('DROP TABLE screen_group');
        $this->addSql('DROP TABLE screen_group_screen');
        $this->addSql('DROP TABLE screen_group_campaign');
        $this->addSql('DROP TABLE screen_layout');
        $this->addSql('DROP TABLE screen_layout_regions');
        $this->addSql('DROP TABLE screen_user');
        $this->addSql('DROP TABLE slide');
        $this->addSql('DROP TABLE slide_media');
        $this->addSql('DROP TABLE template');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_tenant');
    }
}
