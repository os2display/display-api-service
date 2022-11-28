<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221128121323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A6BBD148');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A98260155');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A41A67722');
        $this->addSql('DROP INDEX unique_idx ON playlist_screen_region');
        $this->addSql('CREATE UNIQUE INDEX unique_playlist_screen_region ON playlist_screen_region (playlist_id, screen_id, region_id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A6BBD148');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A41A67722');
        $this->addSql('ALTER TABLE playlist_screen_region DROP FOREIGN KEY FK_6869486A98260155');
        $this->addSql('DROP INDEX unique_playlist_screen_region ON playlist_screen_region');
        $this->addSql('CREATE UNIQUE INDEX unique_idx ON playlist_screen_region (playlist_id, screen_id, region_id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)');
    }
}
