<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211028105837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE theme (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', css_styles LONGTEXT NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE slide ADD theme_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6259027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('CREATE INDEX IDX_72EFEE6259027487 ON slide (theme_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6259027487');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP INDEX IDX_72EFEE6259027487 ON slide');
        $this->addSql('ALTER TABLE slide DROP theme_id');
    }
}
