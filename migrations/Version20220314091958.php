<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314091958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template ADD tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('UPDATE template AS t, (SELECT id from tenant LIMIT 1) AS tenant SET t.tenant_id = tenant.id');
        $this->addSql('ALTER TABLE template ADD CONSTRAINT FK_97601F839033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('CREATE INDEX IDX_97601F839033212A ON template (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template DROP FOREIGN KEY FK_97601F839033212A');
        $this->addSql('DROP INDEX IDX_97601F839033212A ON template');
        $this->addSql('ALTER TABLE template DROP tenant_id');
    }
}
