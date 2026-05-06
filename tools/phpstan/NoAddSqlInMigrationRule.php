<?php

declare(strict_types=1);

namespace App\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids `$this->addSql(...)` calls inside Doctrine migration files.
 *
 * Migrations must use Doctrine's Schema tool API
 * (`$schema->createTable()`, `$table->addColumn()`,
 * `$table->addForeignKeyConstraint()`, `$schema->dropTable()`, …) so the
 * generated DDL is platform-agnostic — the same migration runs on any
 * database Doctrine supports (MariaDB, MySQL, Postgres, SQLite, …)
 * without manual translation.
 *
 * See `migrations/Version20260506215847.php` for a worked reference.
 *
 * @implements Rule<MethodCall>
 */
final class NoAddSqlInMigrationRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!str_contains($scope->getFile(), '/migrations/')) {
            return [];
        }

        if (!$node->name instanceof Identifier || 'addSql' !== $node->name->name) {
            return [];
        }

        if (!$node->var instanceof Variable || 'this' !== $node->var->name) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Migrations must use the Schema tool API ($schema->createTable(), '
                .'$table->addColumn(), …) instead of $this->addSql(). Raw SQL ties '
                .'migrations to a single database platform; the Schema tool produces '
                .'platform-native DDL automatically. See migrations/Version20260506215847.php.',
            )
                ->identifier('migrations.noAddSql')
                ->build(),
        ];
    }
}
