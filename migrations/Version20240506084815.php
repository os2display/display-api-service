<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add fields for tracking client status.
 */
final class Version20240506084815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE screen_user ADD release_timestamp INT DEFAULT NULL, ADD release_version VARCHAR(255) DEFAULT NULL, ADD latest_request DATETIME DEFAULT NULL, ADD client_meta JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE screen_user DROP release_timestamp, DROP release_version, DROP latest_request, DROP client_meta');
    }
}
