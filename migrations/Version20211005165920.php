<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005165920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_group_screen (screen_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', screen_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_905749ED82274D27 (screen_group_id), INDEX IDX_905749ED41A67722 (screen_id), PRIMARY KEY(screen_group_id, screen_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE screen_group_screen ADD CONSTRAINT FK_905749ED82274D27 FOREIGN KEY (screen_group_id) REFERENCES screen_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_group_screen ADD CONSTRAINT FK_905749ED41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE screen_group_screen');
    }
}
