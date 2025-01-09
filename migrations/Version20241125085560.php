<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125085560 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update supported_feed_output_type to match new naming.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE feed_source SET supported_feed_output_type = "news" WHERE supported_feed_output_type = "rss"');
        $this->addSql('UPDATE feed_source SET supported_feed_output_type = "story" WHERE supported_feed_output_type = "instagram"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE feed_source SET supported_feed_output_type = "rss" WHERE supported_feed_output_type = "news"');
        $this->addSql('UPDATE feed_source SET supported_feed_output_type = "instagram" WHERE supported_feed_output_type = "story"');
    }
}
