<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314101006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen_layout DROP FOREIGN KEY FK_4BF6E94B9033212A');
        $this->addSql('DROP INDEX IDX_4BF6E94B9033212A ON screen_layout');
        $this->addSql('ALTER TABLE screen_layout DROP tenant_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen_layout ADD tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE screen_layout ADD CONSTRAINT FK_4BF6E94B9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('CREATE INDEX IDX_4BF6E94B9033212A ON screen_layout (tenant_id)');
    }
}
