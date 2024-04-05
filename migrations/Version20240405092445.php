<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update links to media in slide content field to match api version change.
 */
final class Version20240405092445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update links to media in slide content field to match api version change';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE slide SET content = REPLACE(content, \'\\\/v1\\\/media\\\/\', \'\\\/v2\\\/media\\\/\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE slide SET content = REPLACE(content, \'\\\/v2\\\/media\\\/\', \'\\\/v1\\\/media\\\/\')');
    }
}
