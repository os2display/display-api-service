<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220127081757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist ADD is_campaign TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D41A67722');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D6BBD148');
        $this->addSql('ALTER TABLE screen_playlist DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE screen_playlist ADD id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE screen_playlist ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist DROP is_campaign');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D6BBD148');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D41A67722');
        $this->addSql('ALTER TABLE screen_playlist DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE screen_playlist DROP id');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_playlist ADD PRIMARY KEY (screen_id, playlist_id)');
    }
}
