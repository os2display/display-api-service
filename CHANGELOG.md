# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- Consolidated 25 historical 2.x Doctrine migrations into a single schema-dump migration
  representing the end-of-2.7 schema. Upgraders must be on the latest 2.7.x with every migration
  applied, then run `doctrine:migrations:rollup` instead of `doctrine:migrations:migrate`. Fresh
  installs are unaffected and continue to use `migrate`. See `UPGRADE.md` step 3.
- Restored three previously removed `Template` entity properties (`icon`, `resources`,
  `description`) as deprecated, write-only fields with no getters/setters. The columns are kept
  in the consolidated 3.0 schema so fresh installs and 2.x → 3.0 upgraders end up with identical
  schemas, and Doctrine writes a value on every INSERT (the columns are NOT NULL with no DB
  default). The properties and the matching column-drop migration are scheduled for removal in
  3.1.
- Fixed Calendar and Colibo feed configuration urls and added [] result when no locationEndpoint is set.
- Fixed baked-in `.env` shipping `APP_ENV=dev` in the API image; rewritten to `prod` at build time so
  direct reads don't try to bootstrap a dev environment the prod-only dependencies can't satisfy.

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
