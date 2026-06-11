# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- Restructured `UPGRADE.md` into operator and developer guides: the operator guide separates
  application-config migration (now based on 2.8's `app:utils:convert-env-to-3x`) from
  infrastructure config, covers both image-based and bare-metal deployments, and adds pre-upgrade
  and post-upgrade checklists.
- Disabled API Platform's legacy query-parameter validation system
  (`legacy_query_parameter_validation: false`): its classes self-deprecate on load since 3.4,
  spamming the `php` log channel on every request, and none of our filters declare the
  constraints it enforces. The component is removed in API Platform 4.

## [3.0.0-rc6] - 2026-06-10

- Fixed the 2.x → 3.0 screen client auto-upgrade path: images and the release tarball now also
  ship `release.json` at the deprecated `/client/release.json` location polled by 2.x clients,
  so running screens detect the new release and reload into the 3.0 client. See `UPGRADE.md`.
- Surfaced database outages and unusable JWT signing keys as `503 Service Unavailable` with
  `Retry-After` (previously generic 500s and a false `401 Invalid JWT Token`), so API clients
  can tell a temporary outage from an authentication failure and avoid logging out screens;
  reclassifications are logged per ADR 011.

## [3.0.0-rc5] - 2026-06-10

- Added structured, channel-split application logging (ADR 011): per-domain Monolog channels with
  per-channel prod thresholds (`LOG_LEVEL_<CHANNEL>`, falling back to `LOG_LEVEL`), a configurable
  `LOG_PATH`, request/identity/trace context processors, `X-Request-Id` propagation and an
  auth-event subscriber. See `docs/logging.md`.
- Adopted OpenTelemetry semantic-convention log field names, with GDPR-safe client-address
  truncation, secret-key redaction and structured exception serialization; renamed the
  outbound-HTTP channel `app_http` → `outbound_http` (thresholded by `LOG_LEVEL_OUTBOUND_HTTP`;
  `HTTP_CLIENT_LOG_LEVEL` removed) and silenced Symfony's redundant native `http_client` logging.
- Added a `database` log channel and a DBAL middleware that logs MariaDB connection failures
  (by driver error code; `critical`/`error`), so operators without DB access get a signal.
- Enforced the logging conventions in CI with three project-local PHPStan rules
  (`logging.silentCatch`, `logging.interpolatedLogMessage`, `logging.exceptionContextKey`).
- Enabled PHPStan's `reportIgnoresWithoutComments`: inline `@phpstan-ignore` must carry a comment.
- Added Symfony AI Mate (`symfony/ai-mate`, dev-only): a project-aware MCP server run in the
  `phpfpm` container, with a Monolog log-search bridge.
- Sped up the Playwright CI job: parallel workers (`ipc: host` for shared `/dev/shm`), up-front
  parallel image pulls and removal of the redundant `playwright install` step; also pre-pull
  `phpfpm`/`mariadb` in parallel in the PHPUnit, Doctrine and API-spec jobs.
- Fixed an inverted user-type guard in `UserService::activateExternalUser()`.
- Removed a dead, undefined-variable statement in `MediaRepository::getPaginator()`.

## [3.0.0-rc4] - 2026-06-04

- Tuned OPcache in the production image: enabled Symfony class preloading, inlined container
  factories, raised the interned-strings buffer to 32 MB and right-sized
  `PHP_OPCACHE_MAX_ACCELERATED_FILES` to the prod image's actual file count.
- Added an OPcache status probe to the production image: `docker exec <container> opcache-status`
  dumps the FPM pool's OPcache status and configuration as JSON.
- Upgraded `itk-dev/openid-connect-bundle` to 5.0; migrated OIDC exception handling to the new
  `OpenIdConnectExceptionInterface` and added regression tests for the Azure OIDC authenticator.
- Bounded OIDC provider HTTP calls (discovery, JWKS, token exchange) with a timeout, configurable
  via the new `OIDC_HTTP_TIMEOUT` env var (default 5s); previously a hung IdP could tie up a
  php-fpm worker indefinitely.
- Removed the deprecated feed types `SparkleIOFeedType`, `EventDatabaseApiFeedType` and `KobaFeedType`.
  Feed sources referencing a removed type still load (reads degrade, secrets are not exposed), while
  creating or updating a feed source with an unknown feed type now returns HTTP 422 (was 500).
  Run the new `app:feed:remove-deprecated-feed-sources` command to clean up; see `UPGRADE.md`.
- Decoupled the dev compose stack from `itkdev-docker`: self-contained stack with bundled traefik
  (opt-in via `COMPOSE_PROFILES=traefik`) and a shared-frontend overlay for host-level traefik setups.
- Added project-shared Claude Code configuration (hooks, skills, subagents, MCP servers, plugins)
  under `.claude/`.
- Rewrote the consolidated end-of-2.8 migration to Doctrine's Schema tool API;
  added a `NoAddSqlInMigrationRule` PHPStan rule to enforce the convention on future migrations.
- Added a Postgres `Validate Schema` job to the Doctrine workflow as a regression gate against
  entity/migration drift from Postgres compatibility; uses the new `docker-compose.postgres.yml` override.
- Consolidated 25 historical 2.x Doctrine migrations into a single end-of-2.8 schema migration;
  upgraders run `doctrine:migrations:rollup` (see `UPGRADE.md` step 3).
- Restored three deprecated `Template` properties (`icon`, `resources`, `description`) as
  write-only fields; scheduled for removal in 3.1.
- Merged fixes from 2.7.0 into release/3.0.0.
- Added `INSTANT_BOOK_BUSY_INTERVALS_SOURCE` to select between Graph and the slide's calendar feed as the source of busy
  intervals for InstantBook.
- Changed polling interval for instant booking template.
- Fixed admin toast leaking a raw `SyntaxError: Unexpected token '<'` when an upload was rejected
  upstream (e.g. nginx 413); the toast now shows `HTTP <status>` instead.
- Made the media upload max size configurable via the new `MEDIA_MAX_UPLOAD_SIZE_MB` env var.
- Fixed playlist share-target dropdown silently truncating to 30 tenants; it now loads every page.
- Refactored InteractiveController to use a typed `InteractiveSlideActionInput` DTO; regenerated API spec and RTK types.
- Fixed multiple InstantBook bugs: interval boundary overlap, busy-interval timezone, per-resource spam-protect
  throttling, duration validation, error responses (409/4xx), resource cache TTL, and assorted
  typos/string-interpolation issues.
- Added `getBusyIntervals` cache (PT15M) with a shared `validateResourceAccess()` helper, eliminating per-poll Graph
  calls at the cost of up to 15-minute-stale availability in `quickBookOptions`.

## [3.0.0-rc3] - 2026-05-11

- Made the Admin login sidebar text configurable via the new `ADMIN_LOGIN_SCREEN_TEXT`
  env var. The value accepts a small allow-list of HTML tags (sanitized client-side
  with DOMPurify); when empty the sidebar card is hidden entirely. Removed the
  bundled Danish "medarbejder/borger MitID" copy that previously rendered by default.
- Fixed login screen styling issue resulting in header not filling parent in some breakpoints.
- Fixed Calendar and Colibo feed configuration urls and added [] result when no locationEndpoint is set.
- Fixed baked-in `.env` shipping `APP_ENV=dev` in the API image; rewritten to `prod` at build time so
  direct reads don't try to bootstrap a dev environment the prod-only dependencies can't satisfy.
- Aligned API and Nginx image labels with the OCI image spec: dropped deprecated `LABEL maintainer`,
  added `org.opencontainers.image.{authors,vendor,documentation,base.name}`, and fixed the Nginx image's
  `title`/`description` so it stops inheriting the source-repo defaults.
- Bumped the local dev Redis image from `redis:6` to `redis:8`. Production deployments are unaffected
  (they bring their own Redis); Symfony 6.4's cache adapter and the bundled phpredis 6.3 work as-is.
- Switched Symfony session storage to Redis (default `SESSION_HANDLER_DSN=${REDIS_CACHE_DSN}`); set
  `SESSION_HANDLER_DSN=` empty to fall back to PHP's native file handler. Removes the per-session
  `flock` that serialised parallel session-touching requests and lets sessions survive container
  restarts; multi-pod deployments now share session state without sticky routing.
- Switched local dev MariaDB to upstream `mariadb:11.4` LTS (was `itkdev/mariadb:latest`); both 10.11
  and 11.4 LTS are now exercised by a CI matrix in the PHPUnit and Doctrine schema-validate workflows.
  `MARIADB_IMAGE` and `MARIADB_VERSION` env vars override the compose image and Doctrine
  `serverVersion`. Drops the previously-commented `ENCRYPT=1` toggle inherited from the itkdev wrapper.

## [3.0.0-rc2] - 2026-05-05

- Fixed `Create Github Release` workflow that failed cleanup because `node_modules/` was owned by root.
- Restored container `WORKDIR` to `/app` (matches 2.x and the dev compose;
  RC1 had drifted to `/var/www/html`, breaking JWT key mounts).

## [3.0.0-rc1] - 2026-05-04

- Cleaned up and documented CI workflows; api-spec workflow now reports breaking changes.
- Migrated `rector.php` to the `RectorConfig::configure()` builder.
- Gathered all repositories in one Symfony application.
- Changed to vite 7 and rolldown.
- Added ADRs 008 and 009.
- Cleaned up Github Actions workflows.
- Updated PHP dependencies.
- Added Playwright github action.
- Changed how templates are imported.
- Removed propTypes.
- Upgraded redux-toolkit and how api slices are generated.
- Fixed redux-toolkit cache handling.
- Added Taskfile.
- Added update command.
- Added (Client) online-check to public.
- Updated developer documentation.
- Removed admin/access-config.json fetch.
- Aligned with v. 2.5.2.
- Removed themes.
- Added command to migrate config.json files.
- Fix data fetching bug and tests
- Refactored screen layout commands.
- Moved list components (search and checkboxes) around.
- Aligned environment variable names.
- Aligned with v. 2.6.0.
- Added relations checksum feature flag.
- Fixes saving issues described in issue where saving resulted in infinite spinner.
- Fixed loading of routes containing null string values.
- Fixed relations checksum test.
- Optimized release data fetching.
- Optimized list loading.
- Removed fixture length check from test.
- Fixed video overflow.
- Added vitest for frontend unit tests.
- Added spinner when retrieving bind key.
- Added BRND to feed source admin dropdown.
- Upgraded to PHP 8.4.
- Changed default CLIENT_PULL_STRATEGY_INTERVAL value to 10 minutes.
- Updated infrastructure and image build for mono-repo.
- Fixed nginx static-file location to fall back to PHP so LiipImagineBundle can generate missing thumbnails (#370).
- Unified nginx config: dev compose now mounts the production template/nginx.conf so local matches deployed behavior (#370).
- Annotated `.env` so it serves as the canonical, self-documenting Symfony
  env example, with a CI check that enforces it stays in sync with `config/`.
- Switched image build pipeline to GHCR with multi-arch layer caching.
- Aligned the nginx image env-var contract: split `NGINX_FPM_SERVICE` and
  `NGINX_FPM_PORT`, raised upload cap and trusted-proxy CIDR defaults.
- Documented the 3.x operator-facing image-deployment contract in
  `UPGRADE.md` (full `APP_*` → unprefixed rename list, `env_file:` pattern,
  runtime-tuning surfaces).
- Allowed same-origin iframe embedding so the admin's screen/playlist
  preview and fullscreen slide view work (#390).
- Image build now writes `public/release.json` so the client's
  release-loader can fetch it. The same file is included in the GitHub
  Release tarball.
- Changed code analysis tool from psalm to phpstan.
- Changed src/Controller/Api/AuthOidcController.php to get session from request.
- Aligned with release/2.7.0.
- Fixed instagram-feed template display when no entries.
- Notified FeedType: Added support for video media and cleanup implementation.

### NB! Prior to 3.x the project was split into separate repositories

Therefore, changelogs were maintained for each repo. The v2 changelogs have been moved to the `docs/v2-changelogs/`
folder.

- API: [docs/v2-changelogs/api.md](docs/v2-changelogs/api.md)
- Admin: [docs/v2-changelogs/admin.md](docs/v2-changelogs/admin.md)
- Template: [docs/v2-changelogs/template.md](docs/v2-changelogs/template.md)
- Client: [docs/v2-changelogs/client.md](docs/v2-changelogs/client.md)
