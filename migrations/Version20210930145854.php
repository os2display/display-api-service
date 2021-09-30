<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930145854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist_slide (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', slide_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', weight INT DEFAULT 0 NOT NULL, INDEX IDX_D1F3F7219F70CF56 (playlist_id), INDEX IDX_D1F3F7219470F15D (slide_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7219F70CF56 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7219470F15D FOREIGN KEY (slide_id) REFERENCES slide (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE playlist_slide');
    }
}
