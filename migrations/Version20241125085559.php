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
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\Calendar\\CalendarApiFeedType\' WHERE feed_type = \'App\\Feed\\CalendarApiFeedType\'');
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\EventDatabase\\EventDatabaseApiFeedType\' WHERE feed_type = \'App\\Feed\\EventDatabaseApiFeedType\'');
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\Koba\\KobaFeedType\' WHERE feed_type = \'App\\Feed\\KobaFeedType\'');
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\Notified\\NotifiedFeedType\' WHERE feed_type = \'App\\Feed\\NotifiedFeedType\'');
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\Rss\\RssFeedType\' WHERE feed_type = \'App\\Feed\\RssFeedType\'');
        $this->addSql('UPDATE feed_source SET feed_type = \'App\\Feed\\SourceType\\SparkleIO\\SparkleIOFeedType\' WHERE feed_type = \'App\\Feed\\SparkleIOFeedType\'');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
