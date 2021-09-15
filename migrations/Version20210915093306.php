<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210915093306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_layout_regions (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT \'\' NOT NULL, grid_area LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', UNIQUE INDEX UNIQ_D80836ADC288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE media ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified');
        $this->addSql('ALTER TABLE playlist ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified');
        $this->addSql('ALTER TABLE screen ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified');
        $this->addSql('ALTER TABLE screen_group ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified');
        $this->addSql('ALTER TABLE screen_layout ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP regions, DROP created, DROP modified');
        $this->addSql('ALTER TABLE slide ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, CHANGE duration duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE template ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE screen_layout_regions');
        $this->addSql('ALTER TABLE media ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE playlist ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE screen ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE screen_group ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE screen_layout ADD regions LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE slide ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at, CHANGE duration duration INT DEFAULT -1 NOT NULL');
        $this->addSql('ALTER TABLE template ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP created_at, DROP updated_at');
    }
}
