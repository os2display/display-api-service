<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930075216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE slide_media (slide_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', media_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_EBA5772FDD5AFB87 (slide_id), INDEX IDX_EBA5772FEA9FDD75 (media_id), PRIMARY KEY(slide_id, media_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE slide_media ADD CONSTRAINT FK_EBA5772FDD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slide_media ADD CONSTRAINT FK_EBA5772FEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE slide_media');
    }
}
