---
name: add-api-resource
description: Add a new API Platform resource to display-api-service end-to-end — entity, migration, DTO with #[ApiResource], state Provider+Processor, voter, OpenAPI regeneration, and RTK Query enhancement. Use when the user asks to expose a new resource at /v2/..., add a new API endpoint, or wire up a new entity for the Admin/Client to consume.
disable-model-invocation: true
---

# Add a new API Platform resource

## Backwards compatibility — read first

The `/v2/` API is a **published, versioned contract** (ADR 003). The `apispec` CI workflow runs `oasdiff`
against the base branch and **fails the build on any breaking change** (renamed/removed fields, removed
endpoints, changed types, newly required parameters, tightened responses).

- **New resources, new endpoints, new optional fields, new optional query params** — all fine, surface as a
  non-breaking diff comment on the PR.
- **Modifying an existing resource's shape** — must stay backwards-compatible. Add `title` alongside `name`,
  don't rename. Keep both fields in sync via the Processor (write-side) and Provider (read-side).
- **Genuine BC break** — would require bumping to `/v3`. Flag it for human review before doing the work.

This split is why the project routes through DTOs rather than putting `#[ApiResource]` on entities — DTOs are
the API contract, entities are persistence.

## The architecture split

This project does **not** put `#[ApiResource]` on entities. The split is:

- `src/Entity/Tenant/*.php` — Doctrine persistence (extends `AbstractTenantScopedEntity`).
- `src/Dto/*.php` — the **external** API shape, carries `#[ApiResource]` and operation attributes.
- `src/State/*Provider.php`, `src/State/*Processor.php` — translate DTO ↔ Entity for reads/writes.
- `src/Security/Voter/*.php` — per-resource permission checks (tenant scope + role).

Forgetting any of these (or forgetting to regenerate the OpenAPI spec) is what breaks CI.

## Checklist (do them in this order)

1. **Entity** — `src/Entity/Tenant/<Resource>.php`
   - Extend `App\Entity\Tenant\AbstractTenantScopedEntity` (FK to `Tenant` is inherited).
   - Use `Symfony\Component\Uid\Ulid` for the primary key (see existing entities for `#[ORM\Id]` + `UlidType` columns).
   - Add `relationsChecksum` and `relationsModified` traits if the resource has nested collections clients need to invalidate (see `App\Entity\Tenant\Slide` for the canonical example).

2. **Migration** — `migrations/Version<timestamp>.php`
   - Generate via the `add-migration` skill (or `bin/console doctrine:migrations:generate`).
   - **Schema tool API only** — `$schema->createTable(...)`, `$table->addColumn(Types::*, ...)`, `$table->addForeignKeyConstraint(...)`. No `$this->addSql(...)`. PHPStan rule `NoAddSqlInMigrationRule` blocks it. ADR 010.
   - Type references must be `Doctrine\DBAL\Types\Types::*` constants, or `UlidType::NAME` / `RRuleType::RRULE` for the two custom types.
   - `down()` must mirror `up()` symmetrically.

3. **DTO** — `src/Dto/<Resource>.php` and (if mutable) `src/Dto/<Resource>Input.php`
   - Attach `#[ApiResource]` here, not on the entity.
   - Declare operations explicitly: `new GetCollection()`, `new Get()`, `new Post()`, `new Put()`, `new Delete()` — each pointing at the matching Provider/Processor.
   - Set `security:` on each operation so the voter gets a chance (e.g. `'is_granted("ROLE_USER")'`).
   - See `src/Dto/Slide.php` for a complete example with input class, security expressions, and provider/processor wiring.

4. **State classes** — `src/State/<Resource>Provider.php` (extends `AbstractProvider`) and `src/State/<Resource>Processor.php` (extends `AbstractProcessor`)
   - Provider: hydrate DTO from Entity via the repository; respect tenant scope (handled by `TenantExtension` filter, but verify).
   - Processor: validate `<Resource>Input`, persist via repository, return DTO. Use `RelationsChecksumCalculator` only via the entity listener — don't call SQL directly here.
   - Register them as services (autowiring handles this if they extend the abstract base classes).

5. **Voter** — `src/Security/Voter/<Resource>Voter.php`
   - Even if it just delegates to `ROLE_ADMIN` / tenant membership, **create the voter file** so the resource isn't covered by a generic catch-all. The api-resource-reviewer subagent will flag missing voters.

6. **Regenerate OpenAPI** — from the host:

   ```shell
   task generate:api-spec
   ```

   Commits `public/api-spec-v2.yaml` + `public/api-spec-v2.json`. The CI `apispec` workflow fails if these drift.

7. **RTK Query** — if the operation set changed (new endpoint, new method on existing one):

   ```shell
   task generate:redux-toolkit-api
   ```

   Then edit `assets/shared/redux/enhanced-api.ts`:
   - Add the new resource to `tagTypes` if it's a new type.
   - Configure `providesTags` / `invalidatesTags` on the new endpoints so the Admin cache invalidates correctly.

## Validation

After all 7 steps:

```shell
task code-analysis      # PHPStan, will catch addSql in migration
task test:api           # PHPUnit + test-setup re-migrates fresh DB
task coding-standards:php:check
```

Open the resource in the Admin (`task site-open:admin`) and confirm it reads + writes; the `/docs` page should list the new operations under the right tag.

## Common mistakes (called out so I don't repeat them)

- Forgetting to regenerate the API spec → `apispec` CI job fails with a diff.
- Forgetting to enhance `enhanced-api.ts` → Admin shows stale data after mutations.
- Putting `#[ApiResource]` on the entity → bypasses the DTO indirection and leaks persistence shape into the API.
- Missing tenant scope in a custom repository query → cross-tenant data leak (use `MultiTenantRepositoryTrait`, but be aware its raw SQL is MariaDB-specific).
- Migration uses `$this->addSql(...)` → PHPStan rule rejects it, Postgres CI gate also rejects it. See `add-migration` skill.

## Related

- ADR 010: `docs/adr/010-schema-tool-migrations.md`
- ADR 002 (API-first): `docs/adr/002-api-first.md`
- ADR 003 (API versioning): `docs/adr/003-api-versioning.md`
- Subagent `api-resource-reviewer` cross-checks the whole bundle before PR.
