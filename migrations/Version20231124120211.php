<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231124120211 extends AbstractMigration
{
    public function getDescription(): string
    {
        // https://www.doctrine-project.org/projects/doctrine-dbal/en/3.7/reference/types.html#array-types
        return 'Convert fields/columns of type "Array" (deprecated) to type "JSON"';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->convertDbValues('screen_layout_regions', 'grid_area');
        $this->addSql('ALTER TABLE screen_layout_regions CHANGE grid_area grid_area JSON NOT NULL COMMENT \'(DC2Type:json)\'');

        $this->convertDbValues('slide', 'template_options');
        $this->addSql('ALTER TABLE slide CHANGE template_options template_options JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');

        $this->convertDbValues('template', 'resources');
        $this->addSql('ALTER TABLE template CHANGE resources resources JSON NOT NULL COMMENT \'(DC2Type:json)\'');

        $this->convertDbValues('user_role_tenant', 'roles');
        $this->addSql('ALTER TABLE user_role_tenant CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template CHANGE resources resources LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE slide CHANGE template_options template_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE screen_layout_regions CHANGE grid_area grid_area LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE user_role_tenant CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }

    /**
     * Convert data in columns of type "Array" (SQL: Long text) to type JSON (SQL: Long text).
     *
     * These columns contain serialized data and cannot be converted in plain SQL. We have to load
     * the data and re-serialize as JSON.
     *
     * @see https://stackoverflow.com/questions/76525126/how-to-convert-doctrine-type-array-to-doctrine-type-json-in-a-mysql-database
     *
     * @param string $table
     * @param string $column
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function convertDbValues(string $table, string $column): void
    {
        $connection = $this->connection;

        $querySql = sprintf('SELECT id, %s FROM %s', $column, $table);
        $updateSql = sprintf('UPDATE %s SET %s = :encodedData WHERE id = :id', $table, $column);

        // Fetch the existing rows from the table
        $rows = $connection->fetchAllAssociative($querySql);

        // Iterate over each row
        foreach ($rows as $row) {
            $id = $row['id'];
            $data = $row[$column];

            // Unserialize data first since type array is serialize data
            $encodedData = unserialize($data);

            // Update the row with the new encoded data
            $encodedData = json_encode($encodedData); // Encode the data using json_encode

            $connection->executeStatement($updateSql, [
                'encodedData' => $encodedData,
                'id' => $id,
            ]);
        }
    }
}
