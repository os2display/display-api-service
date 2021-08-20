<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210820130402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id INT NOT NULL, ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6A2CA10CC288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist (id INT NOT NULL, ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D782112DC288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_slide (playlist_id INT NOT NULL, slide_id INT NOT NULL, INDEX IDX_D1F3F7216BBD148 (playlist_id), INDEX IDX_D1F3F721DD5AFB87 (slide_id), PRIMARY KEY(playlist_id, slide_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen (id INT AUTO_INCREMENT NOT NULL, screen_layout_id INT NOT NULL, size INT NOT NULL, resolution_width INT NOT NULL, resolution_height INT NOT NULL, location VARCHAR(255) DEFAULT NULL, INDEX IDX_DF4C6130C1ECB8D6 (screen_layout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_playlist (screen_id INT NOT NULL, playlist_id INT NOT NULL, INDEX IDX_ABF1854D41A67722 (screen_id), INDEX IDX_ABF1854D6BBD148 (playlist_id), PRIMARY KEY(screen_id, playlist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout (id INT NOT NULL, grid_rows INT NOT NULL, grid_columns INT NOT NULL, regions LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_4BF6E94BC288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slide (id INT NOT NULL, template_id INT NOT NULL, duration INT DEFAULT NULL, content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', published_from DATETIME DEFAULT NULL, published_to DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_72EFEE62C288C859 (ulid), INDEX IDX_72EFEE625DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template (id INT NOT NULL, icon VARCHAR(255) DEFAULT NULL, ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_97601F83C288C859 (ulid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen ADD CONSTRAINT FK_DF4C6130C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id)');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D41A67722 FOREIGN KEY (screen_id) REFERENCES screen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_playlist ADD CONSTRAINT FK_ABF1854D6BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE625DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D6BBD148');
        $this->addSql('ALTER TABLE screen_playlist DROP FOREIGN KEY FK_ABF1854D41A67722');
        $this->addSql('ALTER TABLE screen DROP FOREIGN KEY FK_DF4C6130C1ECB8D6');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE625DA0FB8');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_slide');
        $this->addSql('DROP TABLE screen');
        $this->addSql('DROP TABLE screen_playlist');
        $this->addSql('DROP TABLE screen_layout');
        $this->addSql('DROP TABLE slide');
        $this->addSql('DROP TABLE template');
    }
}
