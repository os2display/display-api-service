# ADR 010 - Schema tool migrations, no native SQL for DDL

Date: 27-05-2026

## Status

Proposed

## Context

The consolidated end-of-2.8 migration was rewritten from raw `addSql` strings to
Doctrine's Schema tool API (`$schema->createTable()`, `$table->addColumn()`,
`$table->addForeignKeyConstraint()`, …). The same change replaced free-form
type-name strings (`'string'`, `'datetime_immutable'`, …) with `Doctrine\DBAL\Types\Types::*`
constants.

This unblocks the schema-portability regression gate in
`.github/workflows/doctrine.yaml`: the consolidated migration now applies cleanly
on Postgres 16 from the same code path that runs on MariaDB.

Postgres is still **not** a deployment target. Native MariaDB SQL remains in
entity listeners (`MultiTenantRepositoryTrait`, `RelationsChecksumCalculator`),
and runtime queries are untested against Postgres. The conversion is scoped to
migrations for now — but if migrations regress on portability, the schema-level
work is wasted and the gate stops being meaningful.

## Decision

All future migrations must go through Doctrine's Schema tool API. Raw
`$this->addSql(...)` calls inside `migrations/` are disallowed for DDL.

Enforcement:

- `App\PhpStan\NoAddSqlInMigrationRule` (project-local PHPStan rule) fails CI on
  any `addSql` call in `migrations/`.
- The `Validate Schema (postgres:16, experimental)` job in
  `.github/workflows/doctrine.yaml` runs `doctrine:migrations:migrate` against
  Postgres 16 and fails the build on platform-specific DDL.

Type references in migrations must use the `Doctrine\DBAL\Types\Types::*`
constants (plus `UlidType::NAME`, `RRuleType::RRULE` for the two custom types in
this project) rather than free-form strings, so type renames propagate via the
type system.

One existing exception is pre-existing technical debt called out explicitly:

- Entity listeners and a small set of runtime repository methods still execute
  native MariaDB SQL. Out of scope for this ADR; tracked separately.

## Consequences

- Schema-level migration code stays portable to any database Doctrine supports.
  Real Postgres deployment still requires resolving the runtime-SQL debt above.
- Adding a new migration requires using the Schema tool API. Common operations
  (CREATE TABLE, ALTER TABLE, indexes, foreign keys) have direct equivalents.
  Genuine MariaDB-specific changes that the Schema tool can't express need an
  explicit ADR/review to widen the MariaDB-only surface — not a silent
  `addSql`.
- The Postgres CI job is treated as a portability regression gate, not a
  full runtime test. A green run means migrations and entity metadata are
  portable; it does not mean the application works on Postgres.
- Renames of Doctrine type names propagate to migrations via the type system
  rather than requiring grep-and-replace across migration files.
