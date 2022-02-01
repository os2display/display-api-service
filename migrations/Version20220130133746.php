<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220130133746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_campaign (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_636686BDF639F774 (campaign_id), INDEX IDX_636686BD41A67722 (screen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_group_campaign (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', campaign_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_1E364E6EF639F774 (campaign_id), INDEX IDX_1E364E6E82274D27 (screen_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE screen_campaign ADD CONSTRAINT FK_636686BDF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen_campaign ADD CONSTRAINT FK_636686BD41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6EF639F774 FOREIGN KEY (campaign_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD CONSTRAINT FK_1E364E6E82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id)');
        $this->addSql('DROP TABLE screen_playlist');
        $this->addSql('ALTER TABLE playlist ADD is_campaign TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_playlist (screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_ABF1854D41A67722 (screen_id), INDEX IDX_ABF1854D6BBD148 (playlist_id), PRIMARY KEY(screen_id, playlist_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE screen_campaign');
        $this->addSql('DROP TABLE screen_group_campaign');
        $this->addSql('ALTER TABLE playlist DROP is_campaign');
    }
}
