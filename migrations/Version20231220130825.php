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

//        $this->addSql('UPDATE feed p INNER JOIN feed_source c ON p.feed_source_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.feedSource", c.modified_at)');
//        $this->addSql('UPDATE feed p INNER JOIN slide c ON p.id = c.feed_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.slide", c.modified_at)');
//        $this->addSql('UPDATE theme p INNER JOIN media c ON p.logo_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.logo", c.modified_at)');
//        $this->addSql('UPDATE slide p INNER JOIN (SELECT pivot.slide_id, max(c.modified_at) as max_modified_at FROM slide_media pivot INNER JOIN media c ON pivot.media_id=c.id GROUP BY pivot.slide_id) temp ON p.id = temp.slide_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), \'%Y-%m-%d %H:%i:%s\'), max_modified_at), p.relations_modified = JSON_SET(p.relations_modified, "$.media", max_modified_at)');
//        $this->addSql('UPDATE slide p INNER JOIN theme c ON p.theme_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.theme", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE slide p INNER JOIN template c ON p.template_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.templateInfo", c.modified_at)');
//        $this->addSql('UPDATE slide p INNER JOIN feed c ON p.feed_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.feed", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE playlist_slide p INNER JOIN slide c ON p.slide_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.slide", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE playlist p INNER JOIN playlist_slide c ON p.id = c.playlist_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.slides", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_campaign p INNER JOIN playlist c ON p.campaign_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.campaign", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_campaign p INNER JOIN screen c ON p.screen_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.screen", c.modified_at)');
//        $this->addSql('UPDATE screen_group_campaign p INNER JOIN playlist c ON p.campaign_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.campaign", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_group p INNER JOIN (SELECT pivot.screen_group_id, max(c.modified_at) as max_modified_at
//                                  FROM screen_group_screen pivot INNER JOIN screen c ON pivot.screen_id=c.id GROUP BY pivot.screen_group_id) temp ON p.id = temp.screen_group_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), temp.max_modified_at), \'%Y-%m-%d %H:%i:%s\'),
//                    p.relations_modified = JSON_SET(p.relations_modified, "$.screens", temp.max_modified_at)');
//        $this->addSql('UPDATE screen_group p INNER JOIN screen_group_campaign c ON p.id = c.screen_group_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.screenGroupCampaigns", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_group_campaign p INNER JOIN screen_group c ON p.screen_group_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.screenGroup", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE playlist_screen_region p INNER JOIN playlist c ON p.playlist_id = c.id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.playlist", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_layout_regions p INNER JOIN playlist_screen_region c ON p.id = c.region_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.regions", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen_layout p INNER JOIN screen_layout_regions c ON p.id = c.screen_layout_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.regions", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
//        $this->addSql('UPDATE screen p INNER JOIN screen_campaign c ON p.id = c.screen_id
//                SET p.relations_modified_at = DATE_FORMAT(GREATEST(COALESCE(p.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at, COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\')), \'%Y-%m-%d %H:%i:%s\'), p.relations_modified = JSON_SET(p.relations_modified, "$.campaigns", DATE_FORMAT(GREATEST(COALESCE(c.relations_modified_at, \'1970-01-01 00:00:00\'), c.modified_at), \'%Y-%m-%d %H:%i:%s\'))');
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
