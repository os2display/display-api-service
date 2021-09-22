<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210922092544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', license VARCHAR(255) DEFAULT \'\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_slide (playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', slide_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_D1F3F7216BBD148 (playlist_id), INDEX IDX_D1F3F721DD5AFB87 (slide_id), PRIMARY KEY(playlist_id, slide_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_screen_region (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', region_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_6869486A6BBD148 (playlist_id), INDEX IDX_6869486A41A67722 (screen_id), INDEX IDX_6869486A98260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_layout_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', size INT DEFAULT 0 NOT NULL, resolution_width INT DEFAULT 0 NOT NULL, resolution_height INT DEFAULT 0 NOT NULL, location VARCHAR(255) DEFAULT \'\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_DF4C6130C1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_playlist (screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_ABF1854D41A67722 (screen_id), INDEX IDX_ABF1854D6BBD148 (playlist_id), PRIMARY KEY(screen_id, playlist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', grid_rows INT DEFAULT 0 NOT NULL, grid_columns INT DEFAULT 0 NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout_regions (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_layout_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, grid_area LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_D80836ADC1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slide (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', template_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', template_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', duration INT DEFAULT NULL, content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_72EFEE625DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', icon VARCHAR(255) DEFAULT \'\' NOT NULL, resources LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)');
        $this->addSql('ALTER TABLE screen ADD CONSTRAINT FK_DF4C6130C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_layout_regions ADD CONSTRAINT FK_D80836ADC1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE625DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A6BBD148');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D6BBD148');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A41A67722');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D41A67722');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C6130C1ECB8D6');
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836ADC1ECB8D6');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A98260155');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE625DA0FB8');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_slide');
        $this->addSql('DROP TABLE playlist_screen_region');
        $this->addSql('DROP TABLE screen');
        $this->addSql('DROP TABLE screen_playlist');
        $this->addSql('DROP TABLE screen_group');
        $this->addSql('DROP TABLE screen_layout');
        $this->addSql('DROP TABLE screen_layout_regions');
        $this->addSql('DROP TABLE slide');
        $this->addSql('DROP TABLE template');
    }
}
