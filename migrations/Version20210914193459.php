<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210914193459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_group (id INT AUTO_INCREMENT NOT NULL, ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) DEFAULT \'\' NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, created_by VARCHAR(255) DEFAULT \'\' NOT NULL, UNIQUE INDEX UNIQ_10C76481C288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE media ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE license license VARCHAR(255) DEFAULT \'\'');
        $this->addSql('ALTER TABLE playlist ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE screen ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE size size INT DEFAULT 0 NOT NULL, CHANGE resolution_width resolution_width INT DEFAULT 0 NOT NULL, CHANGE resolution_height resolution_height INT DEFAULT 0 NOT NULL, CHANGE location location VARCHAR(255) DEFAULT \'\', CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE screen_layout ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE grid_rows grid_rows INT DEFAULT 0 NOT NULL, CHANGE grid_columns grid_columns INT DEFAULT 0 NOT NULL, CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE slide ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE duration duration INT DEFAULT -1 NOT NULL, CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE template ADD created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, ADD created_by VARCHAR(255) DEFAULT \'\' NOT NULL, DROP created_at, DROP updated_at, CHANGE icon icon VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE title title VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE screen_group');
        $this->addSql('ALTER TABLE media ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE license license VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE playlist ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE screen ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE size size INT NOT NULL, CHANGE resolution_width resolution_width INT NOT NULL, CHANGE resolution_height resolution_height INT NOT NULL, CHANGE location location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE screen_layout ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE grid_rows grid_rows INT NOT NULL, CHANGE grid_columns grid_columns INT NOT NULL, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE slide ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE duration duration INT DEFAULT NULL, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE template ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP created, DROP modified, DROP modified_by, DROP created_by, CHANGE icon icon VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
