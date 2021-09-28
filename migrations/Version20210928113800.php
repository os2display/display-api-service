<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210928113800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_screen_region DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE playlist_screen_region ADD id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE playlist_screen_region ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_screen_region DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE playlist_screen_region DROP id');
        $this->addSql('ALTER TABLE playlist_screen_region ADD PRIMARY KEY (playlist_id, screen_id, region_id)');
    }
}
