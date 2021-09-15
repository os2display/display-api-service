<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210915111304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist_screen_region (id INT AUTO_INCREMENT NOT NULL, playlist_id INT NOT NULL, screen_id INT NOT NULL, region_id INT NOT NULL, INDEX IDX_6869486A6BBD148 (playlist_id), INDEX IDX_6869486A41A67722 (screen_id), INDEX IDX_6869486A98260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD CONSTRAINT FK_6869486A98260155 FOREIGN KEY (region_id) REFERENCES screen_layout_regions (id)');
        $this->addSql('ALTER TABLE slide ADD template_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE template ADD resources LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE playlist_screen_region');
        $this->addSql('ALTER TABLE slide DROP template_options');
        $this->addSql('ALTER TABLE template DROP resources');
    }
}
