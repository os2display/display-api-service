<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enum\UserTypeEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231003195603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_activation_code (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', tenant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_by VARCHAR(255) DEFAULT \'\' NOT NULL, modified_by VARCHAR(255) DEFAULT \'\' NOT NULL, code VARCHAR(255) NOT NULL, code_expire DATETIME NOT NULL, username VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_E88B20159033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_activation_code ADD CONSTRAINT FK_E88B20159033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE user ADD provider_id VARCHAR(255) NOT NULL, ADD user_type VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE user SET provider_id = email');
        $this->addSql('UPDATE user SET user_type = \''.UserTypeEnum::USERNAME_PASSWORD->value.'\' WHERE created_by = \'CLI\'');
        $this->addSql('UPDATE user SET user_type = \''.UserTypeEnum::OIDC_INTERNAL->value.'\' WHERE created_by = \'OIDC\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A53A8AA ON user (provider_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_activation_code DROP FOREIGN KEY FK_E88B20159033212A');
        $this->addSql('DROP TABLE user_activation_code');
        $this->addSql('DROP INDEX UNIQ_8D93D649A53A8AA ON user');
        $this->addSql('ALTER TABLE user DROP provider_id, DROP user_type');
    }
}
