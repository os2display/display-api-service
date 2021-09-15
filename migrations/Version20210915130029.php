<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210915130029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_screen_region ADD ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6869486AC288C859 ON playlist_screen_region (ulid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_6869486AC288C859 ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region DROP ulid');
    }
}
