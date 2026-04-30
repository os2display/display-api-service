# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

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
- Added vitest for frontend unit tests.
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

### NB! Prior to 3.x the project was split into separate repositories

Therefore, changelogs were maintained for each repo. The v2 changelogs have been moved to the `docs/v2-changelogs/`
folder.

- API: [docs/v2-changelogs/api.md](docs/v2-changelogs/api.md)
- Admin: [docs/v2-changelogs/admin.md](docs/v2-changelogs/admin.md)
- Template: [docs/v2-changelogs/template.md](docs/v2-changelogs/template.md)
- Client: [docs/v2-changelogs/client.md](docs/v2-changelogs/client.md)
