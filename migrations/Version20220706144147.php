<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220706144147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE theme ADD logo_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E708F98F144A FOREIGN KEY (logo_id) REFERENCES media (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9775E708F98F144A ON theme (logo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708F98F144A');
        $this->addSql('DROP INDEX UNIQ_9775E708F98F144A ON theme');
        $this->addSql('ALTER TABLE theme DROP logo_id');
    }
}
