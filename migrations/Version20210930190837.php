<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930190837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7219470F15D');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7219F70CF56');
        $this->addSql('DROP INDEX idx_d1f3f7219f70cf56 ON playlist_slide');
        $this->addSql('CREATE INDEX IDX_D1F3F7216BBD148 ON playlist_slide (playlist_id)');
        $this->addSql('DROP INDEX idx_d1f3f7219470f15d ON playlist_slide');
        $this->addSql('CREATE INDEX IDX_D1F3F721DD5AFB87 ON playlist_slide (slide_id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7219470F15D FOREIGN KEY (slide_id) REFERENCES slide (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7219F70CF56 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F7216BBD148');
        $this->addSql('ALTER TABLE playlist_slide DROP FOREIGN KEY FK_D1F3F721DD5AFB87');
        $this->addSql('DROP INDEX idx_d1f3f7216bbd148 ON playlist_slide');
        $this->addSql('CREATE INDEX IDX_D1F3F7219F70CF56 ON playlist_slide (playlist_id)');
        $this->addSql('DROP INDEX idx_d1f3f721dd5afb87 ON playlist_slide');
        $this->addSql('CREATE INDEX IDX_D1F3F7219470F15D ON playlist_slide (slide_id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F7216BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)');
        $this->addSql('ALTER TABLE playlist_slide ADD CONSTRAINT FK_D1F3F721DD5AFB87 FOREIGN KEY (slide_id) REFERENCES slide (id)');
    }
}
