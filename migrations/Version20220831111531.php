<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220831111531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen ADD resolution VARCHAR(255) DEFAULT \'\' NOT NULL, ADD orientation VARCHAR(255) DEFAULT \'\' NOT NULL, DROP resolution_width, DROP resolution_height');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen ADD resolution_width INT DEFAULT 0 NOT NULL, ADD resolution_height INT DEFAULT 0 NOT NULL, DROP resolution, DROP orientation');
    }
}
