<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314103522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screen_layout_tenant (screen_layout_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_4B4C32E9C1ECB8D6 (screen_layout_id), INDEX IDX_4B4C32E99033212A (tenant_id), PRIMARY KEY(screen_layout_id, tenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE screen_layout_regions_tenant (screen_layout_regions_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_90A5AF483026129C (screen_layout_regions_id), INDEX IDX_90A5AF489033212A (tenant_id), PRIMARY KEY(screen_layout_regions_id, tenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template_tenant (template_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', INDEX IDX_45B1CD905DA0FB8 (template_id), INDEX IDX_45B1CD909033212A (tenant_id), PRIMARY KEY(template_id, tenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE screen_layout_tenant ADD CONSTRAINT FK_4B4C32E9C1ECB8D6 FOREIGN KEY (screen_layout_id) REFERENCES screen_layout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_layout_tenant ADD CONSTRAINT FK_4B4C32E99033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_layout_regions_tenant ADD CONSTRAINT FK_90A5AF483026129C FOREIGN KEY (screen_layout_regions_id) REFERENCES screen_layout_regions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_layout_regions_tenant ADD CONSTRAINT FK_90A5AF489033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_tenant ADD CONSTRAINT FK_45B1CD905DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_tenant ADD CONSTRAINT FK_45B1CD909033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE screen_layout DROP FOREIGN KEY FK_4BF6E94B9033212A');
        $this->addSql('DROP INDEX IDX_4BF6E94B9033212A ON screen_layout');
        $this->addSql('ALTER TABLE screen_layout DROP tenant_id');
        $this->addSql('ALTER TABLE screen_layout_regions DROP FOREIGN KEY FK_D80836AD9033212A');
        $this->addSql('DROP INDEX IDX_D80836AD9033212A ON screen_layout_regions');
        $this->addSql('ALTER TABLE screen_layout_regions DROP tenant_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE screen_layout_tenant');
        $this->addSql('DROP TABLE screen_layout_regions_tenant');
        $this->addSql('DROP TABLE template_tenant');
        $this->addSql('ALTER TABLE screen_layout ADD tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE screen_layout ADD CONSTRAINT FK_4BF6E94B9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('CREATE INDEX IDX_4BF6E94B9033212A ON screen_layout (tenant_id)');
        $this->addSql('ALTER TABLE screen_layout_regions ADD tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE screen_layout_regions ADD CONSTRAINT FK_D80836AD9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('CREATE INDEX IDX_D80836AD9033212A ON screen_layout_regions (tenant_id)');
    }
}
