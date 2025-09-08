<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250828084617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename interactive_slide to interactive_slide_config';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE interactive_slide TO interactive_slide_config;');
        $this->addSql('ALTER TABLE interactive_slide_config RENAME INDEX idx_138e544d9033212a TO IDX_D30060259033212A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE interactive_slide_config RENAME INDEX idx_d30060259033212a TO IDX_138E544D9033212A');
        $this->addSql('RENAME TABLE interactive_slide_config TO interactive_slide;');
    }
}
