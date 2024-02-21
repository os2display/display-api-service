<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240221142818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX relations_modified_at_idx ON feed');
        $this->addSql('DROP INDEX modified_at_idx ON feed');
        $this->addSql('ALTER TABLE feed ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON feed (changed)');
        $this->addSql('ALTER TABLE feed_source ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON feed_source (changed)');
        $this->addSql('ALTER TABLE media ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON media (changed)');
        $this->addSql('DROP INDEX modified_at_idx ON playlist');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist');
        $this->addSql('ALTER TABLE playlist ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_screen_region');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist_screen_region (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON playlist_slide');
        $this->addSql('DROP INDEX modified_at_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON playlist_slide (changed)');
        $this->addSql('ALTER TABLE schedule ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen');
        $this->addSql('DROP INDEX modified_at_idx ON screen');
        $this->addSql('ALTER TABLE screen ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_campaign (changed)');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_group (changed)');
        $this->addSql('DROP INDEX relations_modified_at_idx ON screen_group_campaign');
        $this->addSql('DROP INDEX modified_at_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_group_campaign (changed)');
        $this->addSql('ALTER TABLE screen_layout ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_layout (changed)');
        $this->addSql('ALTER TABLE screen_layout_regions ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON screen_layout_regions (changed)');
        $this->addSql('ALTER TABLE screen_user ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX modified_at_idx ON slide');
        $this->addSql('DROP INDEX relations_modified_at_idx ON slide');
        $this->addSql('ALTER TABLE slide ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, DROP relations_modified_at, CHANGE relations_modified relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON slide (changed)');
        $this->addSql('ALTER TABLE template ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON template (changed)');
        $this->addSql('ALTER TABLE tenant ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE theme ADD version INT DEFAULT 1 NOT NULL, ADD changed TINYINT(1) NOT NULL, ADD relations_checksum JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX changed_idx ON theme (changed)');
        $this->addSql('ALTER TABLE user ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user_role_tenant ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX changed_idx ON template');
        $this->addSql('ALTER TABLE template DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON slide');
        $this->addSql('ALTER TABLE slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON slide (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON slide (relations_modified_at)');
        $this->addSql('ALTER TABLE user DROP version');
        $this->addSql('ALTER TABLE schedule DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_group');
        $this->addSql('ALTER TABLE screen_group ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group (relations_modified_at)');
        $this->addSql('ALTER TABLE tenant DROP version');
        $this->addSql('DROP INDEX changed_idx ON feed_source');
        $this->addSql('ALTER TABLE feed_source DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON screen_group_campaign');
        $this->addSql('ALTER TABLE screen_group_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_group_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_group_campaign (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON screen_layout_regions');
        $this->addSql('ALTER TABLE screen_layout_regions ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX changed_idx ON playlist_slide');
        $this->addSql('ALTER TABLE playlist_slide ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_slide (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_slide (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON theme');
        $this->addSql('ALTER TABLE theme DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON playlist');
        $this->addSql('ALTER TABLE playlist ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist (modified_at)');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist (relations_modified_at)');
        $this->addSql('DROP INDEX changed_idx ON feed');
        $this->addSql('ALTER TABLE feed ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON feed (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON feed (modified_at)');
        $this->addSql('DROP INDEX changed_idx ON playlist_screen_region');
        $this->addSql('ALTER TABLE playlist_screen_region ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON playlist_screen_region (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON playlist_screen_region (modified_at)');
        $this->addSql('ALTER TABLE screen_user DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_campaign');
        $this->addSql('ALTER TABLE screen_campaign ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen_campaign (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen_campaign (modified_at)');
        $this->addSql('ALTER TABLE user_role_tenant DROP version');
        $this->addSql('DROP INDEX changed_idx ON screen_layout');
        $this->addSql('ALTER TABLE screen_layout ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX changed_idx ON media');
        $this->addSql('ALTER TABLE media DROP version, DROP changed, DROP relations_checksum');
        $this->addSql('DROP INDEX changed_idx ON screen');
        $this->addSql('ALTER TABLE screen ADD relations_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP version, DROP changed, CHANGE relations_checksum relations_modified JSON DEFAULT \'{}\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE INDEX relations_modified_at_idx ON screen (relations_modified_at)');
        $this->addSql('CREATE INDEX modified_at_idx ON screen (modified_at)');
    }
}
