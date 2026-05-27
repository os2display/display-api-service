# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

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
