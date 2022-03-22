<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220315133038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist_tenant (playlist_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_A12FC8516BBD148 (playlist_id), INDEX IDX_A12FC8519033212A (tenant_id), PRIMARY KEY(playlist_id, tenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist_tenant ADD CONSTRAINT FK_A12FC8516BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_tenant ADD CONSTRAINT FK_A12FC8519033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE playlist_tenant');
    }
}
