# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Dev tooling — always through `task`

This is a Dockerised dev environment. The host has no PHP/Composer/Node — all PHP and JS commands run inside the
`phpfpm` / `node` containers via `task` (which wraps `docker compose exec`). Never invoke `php`, `composer`,
`phpunit`, `phpstan`, `npm`, etc. directly on the host.

Common commands (run from repo root):

- `task` — list all tasks
- `task site-install` / `task site-install-with-fixtures` — reset + bootstrap the local stack
- `task composer -- <cmd>` — composer in the phpfpm container
- `task compose -- exec phpfpm bin/console <cmd>` — Symfony console
- `task test:api -- --filter SomeTest` — PHPUnit; `--` forwards to phpunit; `composer run test-setup` (drop+create
  test DB and re-migrate) runs first
- `task test:unit` — Vitest (JS unit/component)
- `task test:frontend-built` / `task test:frontend-local` — Playwright e2e
- `task coding-standards:check` / `:apply` — php-cs-fixer + prettier + markdownlint + env coverage
- `task code-analysis` — PHPStan (level 6, with project-local rules)
- `task rector:check` / `:apply` — Rector
- `task db:migrate` — Doctrine migrations
- `task generate:api-spec` then `task generate:redux-toolkit-api` — regenerate `public/api-spec-v2.{yaml,json}` and
  the RTK Query client (`assets/shared/redux/generated-api.ts`). Must be done whenever the API surface changes.
- `task app:update` — `bin/console app:update` (migrate + reinstall templates), the production update path

PostToolUse hooks (see `.claude/settings.json`) automatically run php-cs-fixer, phpstan, twig-cs-fixer,
prettier, markdownlint, and env-coverage on files you edit — you don't need to invoke these yourself for
single-file changes, *provided `jq` is installed on the host* (the hooks read the edited file path from the
tool payload via `jq` and fail open — silently no-op — if it is missing). A Stop hook also warns if you change
`src/Dto/` or `src/State/` without regenerating the OpenAPI spec, and a PreToolUse hook rejects
`$this->addSql(...)` in migrations (ADR 010).

### Claude Code prerequisites (one-time, host-side)

A few things teammates install once on their host so the project's hooks, auto-enabled plugin, and MCP server work:

- **jq** — required by the `PreToolUse`/`PostToolUse` hooks in `.claude/settings.json`, which parse the tool
  payload on stdin to find the edited file path. Install: `brew install jq` (macOS) / `apt-get install jq`
  (Debian/Ubuntu). Without it the hooks fail open and silently no-op (no formatting, no addSql/locked-path guard).
- **Intelephense** — required by the `php-lsp@claude-plugins-official` plugin enabled in `.claude/settings.json`.
  Install: `npm install -g intelephense`. Provides go-to-definition, find-references, and inline diagnostics
  across the Entity ↔ DTO ↔ Provider/Processor chain. Static analyser only — does not need host PHP.
- **context7 MCP** — auto-enabled via `.mcp.json` + `.claude/settings.json` `enabledMcpjsonServers`. No
  install step; Claude Code fetches it on first use. Used for live Symfony/API Platform/Doctrine docs lookup.

## The `/v2/` API is versioned — no breaking changes allowed

The HTTP API at `/v2/` is a **published, versioned contract** (ADR 003). Breaking changes — renamed/removed
fields, removed endpoints, changed types, newly required parameters, tightened responses — are gated by
CI: `.github/workflows/apispec.yaml` runs `oasdiff` against the base branch and **fails the build** on any
BC-classified diff (`fail-on: ERR`). Non-breaking additions (new endpoints, new optional fields, new optional
parameters) are fine and surface as a PR comment.

If you need to break a field's shape, add the new field *alongside* the old one and keep both in sync (see
the example in ADR 003 — adding `title` without removing `name`). A genuine BC break would require bumping to
`/v3` and is out of scope for routine work — flag it for human review, do not just push through the gate.

## High-level architecture

This is a **monorepo** (see ADR 008) containing:

- A Symfony 6.4 + **API Platform 3.x** backend (`src/`, `config/`, `migrations/`) — produces the JSON-LD/Hydra API at
  `/v2/...` (see "no breaking changes" above) and OpenAPI docs at `/docs`.
- Two React/Vite frontends — `assets/admin/` (content editor, served at `/admin`) and `assets/client/` (the screen
  client, served at `/client`). Shared code in `assets/shared/`. The admin/client talk to the backend through an
  RTK Query slice generated from the OpenAPI spec.
- Slide templates in `assets/shared/templates/` (rendered both by `/template` preview and by the client).

### Content model (the "big picture")

`Screen → Layout (with ScreenLayoutRegions) → PlaylistScreenRegion → Playlist → PlaylistSlide → Slide → Template/Theme/Media/Feed`.
Plus `ScreenGroup` (collections of screens) and `Campaign` overrides via `ScreenCampaign` / `ScreenGroupCampaign`.
See the partial class diagram in `README.md` and the entity classes in `src/Entity/Tenant/`.

### Multi-tenancy

- Every content entity extends `App\Entity\Tenant\AbstractTenantScopedEntity` (FK to `Tenant`).
- Tenant scoping is enforced via `App\Security\TenantScopedAuthenticator` + Doctrine extensions in
  `src/Filter/TenantExtension.php` and voters in `src/Security/Voter/`. New tenant-scoped repository queries should
  use `MultiTenantRepositoryTrait` (which still emits MariaDB-specific SQL — see "Schema portability" below).
- A user has roles per tenant via `UserRoleTenant`; the JWT carries the active tenant.

### API Platform layer

API endpoints are declared via attributes on **DTO classes in `src/Dto/`** (the external API shape), not directly on
entities. Each DTO has a matching **State Provider** (`src/State/*Provider.php`, reads) and **Processor**
(`src/State/*Processor.php`, writes) that translate between DTO ↔ Entity. When adding a resource:

1. Add/update entity in `src/Entity/Tenant/`.
2. Add migration in `migrations/` using the Schema tool API (see below).
3. Add/update DTO in `src/Dto/` with the `#[ApiResource]` attribute and operations.
4. Add/update provider + processor in `src/State/`.
5. `task generate:api-spec && task generate:redux-toolkit-api`, then enhance `assets/shared/redux/enhanced-api.ts`
   with tag invalidation for the new endpoints.

### Authentication

Two flows, both via Lexik JWT:

- `/v2/authentication/token` — username/password or OIDC for users (admin/editor). OIDC has two providers:
  `internal` (authoritative — grants tenant access from claims like `Example1Admin`) and `external` (only
  authenticates; tenant access granted later via activation code). See `App\Security\AzureOidcAuthenticator`,
  `ExternalUserAuthenticator`.
- `/v2/authentication/screen` — separate flow for screen clients (`ScreenAuthenticator`, `ScreenUser`).

API is stateless except for these auth routes.

### `relationsModified` checksum tree

Entities expose a `relationsModified` map of SHA1 checksums of their related collections so clients can detect
whether they need to re-fetch nested data. Recalculated in `App\EventListener\RelationsModifiedAtListener`
(postFlush) by `App\DBAL\RelationsChecksumCalculator` using raw MariaDB SQL that walks the tree bottom-up. This
listener is intentionally MariaDB-specific and is one of the known-non-portable parts of the runtime (see ADR 010).

### Feeds

External data sources (RSS, calendar APIs, Microsoft Graph, custom HTTP, …) implement
`App\Feed\FeedTypeInterface` and normalise their output into a small set of well-known **output models**
(`src/Feed/OutputModel/`). Slide templates render against output models, decoupling them from feed implementations.
A `FeedSource` is the configured instance (URL + secrets); a `Feed` ties a `Slide` to a `FeedSource` with per-slide
configuration. Manage via the `app:feed:*` console commands.

## Schema portability & migrations (ADR 010)

- Default DB is **MariaDB 11.4**. CI also exercises MariaDB 10.11 (`.github/workflows/{phpunit,doctrine}.yaml`
  matrix). To run locally against 10.11, set `MARIADB_IMAGE=mariadb:10.11` and `MARIADB_VERSION=10.11.13-MariaDB`
  (the latter goes into Doctrine's `serverVersion` and must match — see `.env`).
- **Postgres is a portability regression gate, not a deployment target.** The `Validate Schema (postgres:16,
  experimental)` job in `doctrine.yaml` runs migrations against Postgres 16; a green run does not mean the app
  works on Postgres.
- **No `addSql` in `migrations/`** — enforced by `App\PhpStan\NoAddSqlInMigrationRule` (in `tools/phpstan/`,
  wired in `phpstan.dist.neon`). All DDL must go through Doctrine's Schema tool API
  (`$schema->createTable(...)`, `$table->addColumn(...)`, …) and use `Doctrine\DBAL\Types\Types::*` constants
  (plus `UlidType::NAME`, `RRuleType::RRULE`) rather than free-form strings.
- Entity listeners and `MultiTenantRepositoryTrait` still emit native MariaDB SQL — that's known tech debt, out of
  scope of ADR 010, but means runtime queries are not yet Postgres-safe.

## Files to leave alone

- `composer.lock`, `package-lock.json` — generated; let composer/npm update them.
- `public/api-spec-v2.yaml`, `public/api-spec-v2.json` — regenerated by `task generate:api-spec`.
- `assets/shared/redux/generated-api.ts` — regenerated by `task generate:redux-toolkit-api`. Customise via
  `assets/shared/redux/enhanced-api.ts`.
- `.env.local`, `.env.*.local` — operator/local secrets, not committed.
- `phpstan-baseline.neon` — regenerate with `task phpstan:generate-baseline` rather than hand-editing.

A PreToolUse hook blocks edits to these.

## Coding standards & quality gates

PRs must pass the workflows in `.github/workflows/` (php-cs-fixer, phpstan, rector, phpunit on the MariaDB matrix,
playwright, vitest, env-coverage, twig, markdownlint, prettier, composer-normalize, apispec drift, doctrine
schema-portability). Run `task run-checks-and-tests` locally to mirror the bulk of these.

Every Symfony env var referenced under `config/` must be documented in `.env` — `scripts/check-env-coverage.sh`
(also run as a PostToolUse hook) enforces this.

## Docs

- `docs/adr/` — architectural decisions (read these before deviating from established patterns).
- `docs/configuration/` — OIDC, calendar feed, and other integration setup.
- `docs/test-guide/test-guide.md` — manual test checklist.
- `README.md` — extensive reference for config env vars, feed types, templates, and screen-status semantics.
- `UPGRADE.md` — version-to-version upgrade notes.
