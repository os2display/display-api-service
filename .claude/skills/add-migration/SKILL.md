---
name: add-migration
description: Create a Doctrine migration in display-api-service using the Schema tool API only (no raw SQL DDL). Use when the user asks to add a migration, change the database schema, add/remove a column, or follow up an entity change. Enforces ADR 010 and the PHPStan NoAddSqlInMigrationRule.
disable-model-invocation: true
---

# Add a Doctrine migration (Schema tool API only)

ADR 010 forbids `$this->addSql(...)` in `migrations/`. Two gates enforce it:

- `App\PhpStan\NoAddSqlInMigrationRule` — PHPStan rejects any `addSql` call under `migrations/`.
- `.github/workflows/doctrine.yaml` — `Validate Schema (postgres:16, experimental)` runs `doctrine:migrations:migrate` on Postgres 16; platform-specific DDL fails the build.

A PreToolUse hook also blocks `Edit`/`Write` to `migrations/Version*.php` that contains `$this->addSql`.

## Generate the skeleton

```shell
task compose -- exec phpfpm bin/console doctrine:migrations:generate
```

This drops `migrations/Version<timestamp>.php` with empty `up()` / `down()` bodies and an injected `$this->isMariaDbServer()` check. **Delete that platform check** — Schema tool calls are portable; the check is only useful when emitting native SQL.

## Authoring rules

1. **Use the Schema tool**, not `addSql`:

   ```php
   use Doctrine\DBAL\Schema\Schema;
   use Doctrine\DBAL\Types\Types;
   use Symfony\Bridge\Doctrine\Types\UlidType;
   use App\DBAL\Types\RRuleType;

   public function up(Schema $schema): void
   {
       $table = $schema->createTable('foo');
       $table->addColumn('id', UlidType::NAME);
       $table->addColumn('tenant_id', UlidType::NAME);
       $table->addColumn('title', Types::STRING, ['length' => 255]);
       $table->addColumn('config', Types::JSON, ['notnull' => false]);
       $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
       $table->setPrimaryKey(['id']);
       $table->addIndex(['tenant_id'], 'IDX_foo_tenant');
       $table->addForeignKeyConstraint('tenant', ['tenant_id'], ['id'], [], 'FK_foo_tenant');
   }

   public function down(Schema $schema): void
   {
       $schema->dropTable('foo');
   }
   ```

2. **Type references** must be one of:
   - `Doctrine\DBAL\Types\Types::*` constants (`STRING`, `TEXT`, `INTEGER`, `BIGINT`, `BOOLEAN`, `DATETIME_IMMUTABLE`, `JSON`, `BLOB`, …).
   - `Symfony\Bridge\Doctrine\Types\UlidType::NAME` for ULID primary keys.
   - `App\DBAL\Types\RRuleType::RRULE` for the RRule column on `Schedule`.
   - **Not** free-form strings like `'string'` or `'datetime_immutable'` — these break when a Doctrine type is renamed.

3. **`down()` must mirror `up()` symmetrically.** Drop tables/columns/indexes in reverse order. Test-setup (`composer run test-setup`) drops the test DB every run, but production migrations may be rolled back.

4. **Tenant-scoped tables** need a `tenant_id` column + FK + index. See `Slide`, `Playlist`, etc.

5. **ULID primary keys** are the project default. Use `UlidType::NAME` not `Types::GUID` or `Types::STRING`.

## When you genuinely need MariaDB-specific DDL

The Schema tool can't express every MariaDB feature (e.g. specific collation tweaks, FULLTEXT indexes with options). Before reaching for `addSql`:

1. Try Doctrine `$table->addOption('charset', 'utf8mb4')` or similar — most common needs are covered.
2. If not, **write an ADR amendment** documenting why this migration widens the MariaDB-only surface. Don't silently bypass the rule.

## Validation

```shell
task code-analysis     # PHPStan — catches addSql
task test:api          # test-setup re-migrates fresh DB
```

The full Postgres portability gate runs only in CI (`Validate Schema (postgres:16, experimental)` in `.github/workflows/doctrine.yaml`), but locally `task code-analysis` is enough to catch the disallowed-`addSql` failure mode.

## Related

- ADR 010: `docs/adr/010-schema-tool-migrations.md`
- PHPStan rule: `tools/phpstan/NoAddSqlInMigrationRule.php`
- The `add-api-resource` skill calls this one when a new entity needs a migration.
