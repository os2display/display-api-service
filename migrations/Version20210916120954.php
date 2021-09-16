<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210916120954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen_layout_regions ADD screen_layout_id INT NOT NULL');
        $this->addSql('ALTER TABLE screen_layout_regions ADD CONSTRAINT FK_D80836ADC1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('CREATE INDEX IDX_D80836ADC1ECB8D6 ON screen_layout_regions (screen_layout_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836ADC1ECB8D6');
        $this->addSql('DROP INDEX IDX_D80836ADC1ECB8D6 ON screen_layout_regions');
        $this->addSql('ALTER TABLE screen_layout_regions DROP screen_layout_id');
    }
}
