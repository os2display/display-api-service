<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930192128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE playlist_slide DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE playlist_slide ADD id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', ADD weight INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('ALTER TABLE playlist_slide DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE playlist_slide DROP id, DROP weight');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_slide ADD PRIMARY KEY (playlist_id, slide_id)');
    }
}
