<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * FIXME: The up and down migrations are equal. That's weird!
 */
final class Version20231006120901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed CHANGE configuration configuration JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE feed_source CHANGE secrets secrets JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE screen_user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE slide CHANGE content content JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed CHANGE configuration configuration JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE feed_source CHANGE secrets secrets JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE screen_user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE slide CHANGE content content JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
