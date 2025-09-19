<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250815092648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes unused Template properties';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template DROP icon, DROP resources, DROP description');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template ADD icon VARCHAR(255) DEFAULT \'\' NOT NULL, ADD resources JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD description VARCHAR(255) DEFAULT \'\' NOT NULL');
    }
}
