<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125085559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update namespacing for FeedTypes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\CalendarApi\\\\CalendarApiFeedType" WHERE feed_type = "App\\\\Feed\\\\CalendarApiFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\CalendarKoba\\\\KobaFeedType" WHERE feed_type = "App\\\\Feed\\\\KobaFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\NewsColibo\\\\ColiboFeedType" WHERE feed_type = "App\\\\Feed\\\\ColiboFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\NewsRss\\\\RssFeedType" WHERE feed_type = "App\\\\Feed\\\\RssFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\PosterEventDatabase\\\\EventDatabaseApiFeedType" WHERE feed_type = "App\\\\Feed\\\\EventDatabaseApiFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\StoryNotified\\\\NotifiedFeedType" WHERE feed_type = "App\\\\Feed\\\\NotifiedFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SourceType\\\\StorySparkleIO\\\\SparkleIOFeedType" WHERE feed_type = "App\\\\Feed\\\\SparkleIOFeedType"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\CalendarApiFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\CalendarApi\\\\CalendarApiFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\KobaFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\CalendarKoba\\\\KobaFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\ColiboFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\NewsColibo\\\\ColiboFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\RssFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\NewsRss\\\\RssFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\EventDatabaseApiFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\PosterEventDatabase\\\\EventDatabaseApiFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\NotifiedFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\StoryNotified\\\\NotifiedFeedType"');
        $this->addSql('UPDATE feed_source SET feed_type = "App\\\\Feed\\\\SparkleIOFeedType" WHERE feed_type = "App\\\\Feed\\\\SourceType\\\\StorySparkleIO\\\\SparkleIOFeedType"');
    }
}
