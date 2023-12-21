<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\EventListener\RelationsModifiedAtListener;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220130825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON feed (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON feed (modified_at)');
        $this->addSql('ALTER TABLE playlist ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist (modified_at)');
        $this->addSql('ALTER TABLE playlist_screen_region ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_screen_region (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_screen_region (modified_at)');
        $this->addSql('ALTER TABLE playlist_slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_slide (modified_at)');
        $this->addSql('ALTER TABLE screen ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen (modified_at)');
        $this->addSql('ALTER TABLE screen_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_campaign (modified_at)');
        $this->addSql('ALTER TABLE screen_group ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group (modified_at)');
        $this->addSql('ALTER TABLE screen_group_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group_campaign (modified_at)');
        $this->addSql('ALTER TABLE screen_layout ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE screen_layout_regions ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON slide (modified_at)');
        $this->addSql('ALTER TABLE theme ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON theme (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON theme (modified_at)');

        $sqlQueries = RelationsModifiedAtListener::getUpdateRelationsAtQueries(withWhereClause: false);

        foreach ($sqlQueries as $sqlQuery) {
            $this->addSql($sqlQuery);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist');
        $this->addSql('DROP INDEX modified_at_idx ON playlist');
        $this->addSql('ALTER TABLE playlist DROP relations_modified_at, DROP relations_modified');
        $this->addSql('ALTER TABLE screen_layout DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen');
        $this->addSql('DROP INDEX modified_at_idx ON screen');
        $this->addSql('ALTER TABLE screen DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_slide');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON theme');
        $this->addSql('DROP INDEX modified_at_idx ON theme');
        $this->addSql('ALTER TABLE theme DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON slide');
        $this->addSql('DROP INDEX modified_at_idx ON slide');
        $this->addSql('ALTER TABLE slide DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON feed');
        $this->addSql('DROP INDEX modified_at_idx ON feed');
        $this->addSql('ALTER TABLE feed DROP relations_modified_at, DROP relations_modified');
        $this->addSql('ALTER TABLE screen_layout_regions DROP relations_modified_at, DROP relations_modified');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_screen_region');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region DROP relations_modified_at, DROP relations_modified');
    }
}
