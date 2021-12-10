<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211210125756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feed (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', feed_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_234044ABDDAEFFBD (feed_source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_source (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', feed_type VARCHAR(255) NOT NULL, output_type VARCHAR(255) NOT NULL, secrets LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', configuration LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT FK_234044ABDDAEFFBD FOREIGN KEY (feed_source_id) REFERENCES feed_source (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed DROP FOREIGN KEY FK_234044ABDDAEFFBD');
        $this->addSql('DROP TABLE feed');
        $this->addSql('DROP TABLE feed_source');
    }
}
