# Upgrade Guide

## Table of contents

- [2.x -> 3.0](#2x---30)
  - [What changed](#what-changed)
  - [Operator guide](#operator-guide)
    - [Pre-upgrade checklist (while still on 2.x)](#pre-upgrade-checklist-while-still-on-2x)
    - [Step 1 — Application configuration](#step-1--application-configuration)
    - [Step 2 — Infrastructure configuration](#step-2--infrastructure-configuration)
      - [Option A: os2display-docker-server](#option-a-os2display-docker-server)
      - [Option B: published images, own container orchestration](#option-b-published-images-own-container-orchestration)
      - [Option C: GitHub build artifacts, classic nginx setup](#option-c-github-build-artifacts-classic-nginx-setup)
      - [Option D: bare metal, repo checked out](#option-d-bare-metal-repo-checked-out)
    - [Step 3 — Database and content migration](#step-3--database-and-content-migration)
    - [Post-upgrade sanity checks](#post-upgrade-sanity-checks)
  - [Developer guide](#developer-guide)
    - [Repository changes](#repository-changes)
    - [Convert external templates to custom templates](#convert-external-templates-to-custom-templates)
    - [Removed feed types](#removed-feed-types)

## 2.x -> 3.0

### What changed

3.0 merges the previously separate Admin, Client and Templates repositories into the API repository,
<https://github.com/os2display/display-api-service>, which now contains the complete OS2display
project. The old admin, client and templates repositories will be archived when 2.x no longer
receives security updates.

Consequences for an upgrade:

- **One stack.** A single application now serves the API, the admin and the screen client. Separate
  admin/client containers (and their `config.json` files) go away.
- **Configuration moves to environment variables.** Admin and client settings previously in
  `config.json` become `ADMIN_*`/`CLIENT_*` env variables, and the `APP_*` prefix translation used
  by the 2.x compose stack is gone — names in `.env.local` must match the Symfony names exactly.
- **Templates are bundled.** Templates are no longer loaded from external URLs; they ship with the
  code. External 2.x templates must be converted to custom templates (developer task, see below).
- **Removed feed types.** `SparkleIOFeedType`, `EventDatabaseApiFeedType` and `KobaFeedType` were
  deprecated in 2.x and removed in 3.x. Feed sources using them keep loading (reads degrade,
  writes are rejected with HTTP 422) but can no longer fetch data.

The guide below is split by role. Operators upgrade a running installation; developers maintain
custom templates or work on the code.

---

### Operator guide

#### Pre-upgrade checklist (while still on 2.x)

- [ ] Upgrade the installation to the **latest 2.8.x** release.
- [ ] All 2.x migrations applied — `doctrine:migrations:status` reports no new (pending) migrations.
- [ ] Back up the database.
- [ ] Export the configuration in 3.x shape (the converter ships with 2.8):

  ```shell
  docker compose exec -T phpfpm bin/console app:utils:convert-env-to-3x \
    --output=env --app-url=https://display.example.com > env.3x
  ```

  The command converts everything the running 2.x application has loaded — env variables *and* the
  admin/client `config.json` — to 3.x names, no matter how the installation injects its config.
  Review the notes on stderr: the trailing advisory lists infrastructure variables (`COMPOSE_*`,
  `PHP_*`, `NGINX_*`, `MARIADB_*`) that belong in container/orchestration config in 3.x, never in
  the application env.

- [ ] If the installation uses **external templates**, plan their conversion to custom templates
  before the upgrade — see the [developer guide](#developer-guide). The template `id` must be kept.
- [ ] Check for feed sources using removed feed types (SparkleIO, EventDatabaseApi v1, Koba) so
  their removal in [step 3](#step-3--database-and-content-migration) comes as no surprise. Run the
  report on the upgraded code (it lists, changes nothing without `--force`):

  ```shell
  bin/console app:feed:remove-deprecated-feed-sources
  ```

#### Step 1 — Application configuration

Use the exported `env.3x` from the pre-upgrade checklist as the starting point for the 3.x
`.env.local`.

<details>
<summary>Manual fallback (only if you skipped the pre-upgrade export)</summary>

The pre-upgrade `app:utils:convert-env-to-3x` already emits the complete 3.x env — including the
`ADMIN_*`/`CLIENT_*` values from the admin/client `config.json`, which it fetches from the running
2.x app. It is the only conversion you need; there is no separate `config.json` step.

If the 2.x installation is still reachable, simply run it now against the live site
(`--app-url=https://display.example.com`) rather than converting anything by hand.

If 2.x is already gone, rename every `APP_X` variable from the old `.env.docker.local` to `X`,
with these exceptions:

- `APP_ENV`, `APP_DEBUG` and `APP_SECRET` are Symfony-defined and keep their prefix.
- `APP_ACTIVATION_CODE_EXPIRE_INTERNAL` → `ACTIVATION_CODE_EXPIRE_INTERVAL` (typo fixed).
- `APP_HTTP_CLIENT_LOG_LEVEL` → `LOG_LEVEL_OUTBOUND_HTTP`.

The full, authoritative rename table is the `ENV_MAP` constant in the 2.8 `convert-env-to-3x`
command. For the `ADMIN_*`/`CLIENT_*` settings that previously lived in `config.json`, see the
[README configuration reference](README.md#configuration).

</details>

Sanity check — `.env.local` must at least set:

- [ ] `APP_ENV=prod` and `APP_SECRET`
- [ ] `DATABASE_URL`
- [ ] `JWT_PASSPHRASE` (matching the existing keypair in `config/jwt/` — keep it from 2.x)
- [ ] `CORS_ALLOW_ORIGIN`
- [ ] OIDC settings (`INTERNAL_OIDC_*` and/or `EXTERNAL_OIDC_*`)
- [ ] `ADMIN_*` / `CLIENT_*` (previously `config.json`; see [README](README.md#configuration))

#### Step 2 — Infrastructure configuration

The 2.x stack ran separate api/admin/client containers. In 3.0 a single application replaces them.
Follow the option matching how you host.

##### Option A: os2display-docker-server

The 3.0 branch of
[os2display-docker-server](https://github.com/os2display/os2display-docker-server) wires up the
published images for you, and its `task env:migrate` / `task env:diff` automate the env migration.
Follow the upgrade instructions in that repository.

##### Option B: published images, own container orchestration

Production 3.x deployments run two images, built and published from this repository:

- `ghcr.io/os2display/display-api-service` — the php-fpm application
- `ghcr.io/os2display/display-api-service-nginx` — nginx, serving static files and forwarding PHP requests

1. Point your compose stack at the images and reference the application config via `env_file:`:

   ```yaml
   services:
     api:
       image: ghcr.io/os2display/display-api-service:<tag>
       env_file:
         - .env.local
   ```

2. Persist state across container rebuilds with volume mounts:
   - `config/jwt/` — the JWT keypair (reuse the 2.x keypair and `JWT_PASSPHRASE`).
   - `public/media/` — uploaded media.
3. Runtime tuning is independent of the Symfony env surface and is passed to the respective images
   via compose `environment:`:
   - nginx: `NGINX_PORT`, `NGINX_FPM_SERVICE`, `NGINX_FPM_PORT`, `NGINX_MAX_BODY_SIZE`,
     `NGINX_SET_REAL_IP_FROM`, `NGINX_WEB_ROOT` (defaults in `infrastructure/nginx/Dockerfile`).
   - PHP-FPM: `PHP_MEMORY_LIMIT`, `PHP_MAX_EXECUTION_TIME`, `PHP_POST_MAX_SIZE`,
     `PHP_UPLOAD_MAX_FILESIZE`, `PHP_PM_*`, `PHP_OPCACHE_*` (consumed by the `itkdev/php8.4-fpm`
     base image).
4. Screen client auto-upgrade: nothing to do. The images ship `release.json` both at the new
   location (site root) and at the deprecated `/client/release.json` path polled by 2.x clients, so
   running screens reload into the 3.0 client on their next release check (every 10 minutes by
   default).

If you want a fully documented reference of every Symfony env variable the application consumes,
the image ships a self-documenting `.env`:

```shell
docker run --rm ghcr.io/os2display/display-api-service:<tag> cat /var/www/html/.env
```

##### Option C: GitHub build artifacts, classic nginx setup

Every release on <https://github.com/os2display/display-api-service/releases> ships a pre-built
production tarball (`display-api-service-<version>.tar.gz` + `checksum.txt`): prod composer
dependencies and built frontend included, no Composer or Node needed on the host.

Requirements: PHP 8.4 (fpm) with the usual Symfony extensions, nginx.

1. Download the tarball, verify the checksum and unpack it into the web root:

   ```shell
   sha256sum -c checksum.txt
   mkdir -p /var/www/display && tar -xzf display-api-service-<version>.tar.gz -C /var/www/display
   ```

   The archive has no top-level directory — its contents (`public/`, `src/`, `vendor/`, `.env`, …)
   land directly in `/var/www/display`, so the command above unpacks into the web root as-is.

2. Place the `.env.local` from step 1 in the unpacked root. Keep `config/jwt/` and `public/media/`
   from 2.x.
3. Serve `public/` with nginx; use `infrastructure/nginx/etc/templates/default.conf.template` (in
   the repository — it is not part of the tarball) as the reference configuration.
4. Screen client auto-upgrade: nothing to do. The tarball ships `release.json` both at the new
   location (site root) and at the deprecated `/client/release.json` path polled by 2.x clients.
5. Restart php-fpm after deploy — Symfony reads its configuration once at boot.

##### Option D: bare metal, repo checked out

Requirements: PHP 8.4 (fpm) with the usual Symfony extensions, Composer 2, Node 24, nginx.

1. Check out the 3.0 release:

   ```shell
   git fetch --tags && git checkout <3.0-tag>
   ```

2. Place the `.env.local` from step 1 in the project root. Keep `config/jwt/` and `public/media/`
   from 2.x.
3. Install dependencies and build the frontend (admin + client are served from `public/build`):

   ```shell
   APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative
   npm ci && npm run build
   ```

4. Serve `public/` with nginx; use `infrastructure/nginx/etc/templates/default.conf.template` as
   the reference configuration.
5. Screen client auto-upgrade: nothing generates the release file on source deploys — your deploy
   process must write it (shape per `docs/release-example.json`), including a copy at the
   deprecated location polled by 2.x clients:

   ```shell
   printf '{"releaseTimestamp": %s, "releaseTime": "%s", "releaseVersion": "%s"}\n' \
     "$(date +%s)" "$(date -u)" "<version>" > public/release.json
   cp public/release.json public/client/release.json
   ```

6. Restart php-fpm after deploy — Symfony reads its configuration once at boot.

#### Step 3 — Database and content migration

Run these in order on the upgraded code (prefix with `docker compose exec phpfpm` in dockerised
setups).

1. **Roll up migrations.** 3.0 ships a single consolidated migration representing the end-of-2.8
   schema; the 25 historical migrations are removed. Your database already matches that schema, so
   nothing must run — the version-tracking table is rewritten instead:

   ```shell
   bin/console doctrine:migrations:rollup --no-interaction
   ```

   Fresh installs (no 2.x database) skip the rollup and run `doctrine:migrations:migrate` instead.

2. **Install templates and screen layouts:**

   ```shell
   bin/console app:update --no-interaction
   ```

   `app:update` applies migrations and installs/refreshes the bundled templates and screen
   layouts. After the step-1 rollup the consolidated migration is already recorded as executed, so
   the migrate phase here is a no-op — only the template and layout install runs. Inspect the
   result with `bin/console app:templates:list` and
   `bin/console app:screen-layouts:list`; `bin/console app:screen-layouts:install
   --cleanupRegions` removes regions no longer connected to a layout.

3. **Clean up feed sources using removed feed types:**

   ```shell
   # Report feed sources referencing a removed feed type (no changes made):
   bin/console app:feed:remove-deprecated-feed-sources

   # Remove them, their feeds and the slides bound to those feeds:
   bin/console app:feed:remove-deprecated-feed-sources --force
   ```

   Recreate event database feeds using `EventDatabaseApiV2FeedType`.

4. Run the same `app:update` command on every future deploy.

#### Post-upgrade sanity checks

- [ ] `bin/console doctrine:migrations:status` reports no new migrations and a single executed
  version.
- [ ] `bin/console app:templates:list` and `bin/console app:screen-layouts:list` show the expected
  entries as installed.
- [ ] `/admin` loads and login works (username/password and/or OIDC).
- [ ] `https://<host>/release.json` and `https://<host>/client/release.json` both return JSON.
- [ ] An existing screen reconnects without re-pairing — the cheapest single check that the 2.x JWT
  keypair and `JWT_PASSPHRASE` were carried over correctly (a mismatch forces re-pairing).
- [ ] Screens reconnect and show content within ~10 minutes (the 2.x clients auto-upgrade on their
  next release check).
- [ ] Existing media render in slides (the `public/media/` volume survived the switch).
- [ ] Application logs are clean (`docker compose logs` / the configured `LOG_PATH`).

---

### Developer guide

#### Repository changes

- All development happens in <https://github.com/os2display/display-api-service> — admin, client
  and templates code now lives in this repository (`assets/`).
- The dev environment is docker compose based and wrapped by [Taskfile](README.md#taskfile); see
  [README — Development setup](README.md#development-setup) for getting started, the frontend dev
  server and the test suites.

#### Convert external templates to custom templates

Templates are no longer loaded from external URLs — only templates that are part of the code are
included. Standard templates live in `assets/shared/templates/`; custom templates in
`assets/shared/custom-templates/` (documented in
[README — custom templates](README.md#custom-templates)).

To convert a 2.x external template:

1. Port the template code into `assets/shared/custom-templates/`.
2. **Keep the template `id` unchanged** — existing slides reference it.
3. Install it: `bin/console app:templates:list`, then `bin/console app:templates:install`.

Checklist:

- [ ] Template `id` identical to the 2.x external template.
- [ ] `app:templates:list` shows the custom template as available/installed.
- [ ] Existing slides using the template render in preview and on a screen.

#### Removed feed types

`SparkleIOFeedType`, `EventDatabaseApiFeedType` and `KobaFeedType` are removed in 3.0 (deprecated
in 2.x). Unknown feed types are handled consistently: reads degrade (entities load, secrets are not
exposed, feed data endpoints return empty), writes are rejected with HTTP 422. Custom feed type
implementations should follow `EventDatabaseApiV2FeedType` as the reference.
