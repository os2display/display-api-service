<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220113122211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campaign (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_layout_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_1F1512DDC1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign_playlist (campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_E5F2C752F639F774 (campaign_id), INDEX IDX_E5F2C7526BBD148 (playlist_id), PRIMARY KEY(campaign_id, playlist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group_campaign (screen_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_1E364E6E82274D27 (screen_group_id), INDEX IDX_1E364E6EF639F774 (campaign_id), PRIMARY KEY(screen_group_id, campaign_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDC1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE campaign_playlist ADD CONSTRAINT FK_E5F2C752F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_playlist ADD CONSTRAINT FK_E5F2C7526BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6E82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6EF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_screen_region ADD campaign_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', CHANGE screen_id screen_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486AF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('CREATE INDEX IDX_6869486AF639F774 ON playlist_screen_region (campaign_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_playlist DROP FOREIGN KEY FK_E5F2C752F639F774');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486AF639F774');
        $this->addSql('ALTER TABLE screen_group_campaign DROP FOREIGN KEY FK_1E364E6EF639F774');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE campaign_playlist');
        $this->addSql('DROP TABLE screen_group_campaign');
        $this->addSql('DROP INDEX IDX_6869486AF639F774 ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region DROP campaign_id, CHANGE screen_id screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
    }
}
