# Upgrade Guide

## Table of contents

- ### [2.x -> 3.0](#2x---30)

## 2.x -> 3.0

The upgrade from 2.x to 3.0 of OS2Display introduces a major change to the project.
The Admin and Client apps and the Templates that previously existed in separate repositories from the API,
have been included in the API repository.
The API repository has been renamed from <https://github.com/os2display/display-api-service> to
<https://github.com/os2display/display> since it now contains the complete OS2Display project.
The repositories for admin, client and templates will be archived.

Because of these changes, it will be necessary to adjust the server setup to match the new structure.

### Upgrade steps

#### 0.1 - Upgrade the API to the latest version of 2.x

#### 0.2 - Checkout the API to 3.x

#### 1 - Convert external templates to custom templates

Instead of loading javascript for templates from possibly external urls we have made the change to only include
templates that are a part of the code. Standard templates are now located in `assets/shared/templates/`.
Custom templates are located in `assets/shared/custom-templates`.

Because of this change, external templates in 2.x will have to be converted to custom templates.
Custom templates are documented in the [README.md#custom-templates](README.md#custom-templates).

The important thing is that the `id` of the template should remain the same when converted to a custom template.

#### 2 - Configure the following environment variables in `.env.local`

```dotenv
###> Admin configuration ###
ADMIN_REJSEPLANEN_APIKEY=
ADMIN_SHOW_SCREEN_STATUS=false
ADMIN_TOUCH_BUTTON_REGIONS=false
ADMIN_LOGIN_METHODS='[{"type":"username-password","enabled":true,"provider":"username-password","label":""}]'
ADMIN_ENHANCED_PREVIEW=false
###< Admin configuration ###

###> Client configuration ###
CLIENT_LOGIN_CHECK_TIMEOUT=20000
CLIENT_REFRESH_TOKEN_TIMEOUT=300000
CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT=600000
CLIENT_SCHEDULING_INTERVAL=60000
CLIENT_PULL_STRATEGY_INTERVAL=90000
CLIENT_COLOR_SCHEME='{"type":"library","lat":56.0,"lng":10.0}'
CLIENT_DEBUG=false
###< Client configuration ###
```

These values were previously added to Admin and Client: `/public/config.json`.
See [README.md](./README.md) for a description of the configuration options.

You can convert your previous config.json files to .env config with the following commands:

```shell
docker compose exec phpfpm bin/console app:utils:convert-config-json-to-env --type=admin path/to/admin/config.json
docker compose exec phpfpm bin/console app:utils:convert-config-json-to-env --type=client path/to/client/config.json
```

#### 2.1 - Rename environment variables

In 3.x the compose stack carries no `APP_*` prefix translation — variable
names in `.env.local` must match the Symfony names exactly. Every
`APP_X` variable from the previous 2.x `.env.docker.local` is renamed
to `X`, with the **exception of `APP_ENV` and `APP_SECRET`** which are
Symfony-defined and keep their prefix.

The full rename list:

```text
APP_TRUSTED_PROXIES                              → TRUSTED_PROXIES
APP_DATABASE_URL                                 → DATABASE_URL
APP_CORS_ALLOW_ORIGIN                            → CORS_ALLOW_ORIGIN
APP_DEFAULT_DATE_FORMAT                          → DEFAULT_DATE_FORMAT
APP_ACTIVATION_CODE_EXPIRE_INTERVAL              → ACTIVATION_CODE_EXPIRE_INTERVAL
APP_KEY_VAULT_SOURCE                             → KEY_VAULT_SOURCE
APP_KEY_VAULT_JSON                               → KEY_VAULT_JSON
APP_TRACK_SCREEN_INFO                            → TRACK_SCREEN_INFO
APP_TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS    → TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS

APP_JWT_SECRET_KEY                               → JWT_SECRET_KEY
APP_JWT_PUBLIC_KEY                               → JWT_PUBLIC_KEY
APP_JWT_PASSPHRASE                               → JWT_PASSPHRASE
APP_JWT_TOKEN_TTL                                → JWT_TOKEN_TTL
APP_JWT_SCREEN_TOKEN_TTL                         → JWT_SCREEN_TOKEN_TTL
APP_JWT_REFRESH_TOKEN_TTL                        → JWT_REFRESH_TOKEN_TTL
APP_JWT_SCREEN_REFRESH_TOKEN_TTL                 → JWT_SCREEN_REFRESH_TOKEN_TTL

APP_REDIS_CACHE_PREFIX                           → REDIS_CACHE_PREFIX
APP_REDIS_CACHE_DSN                              → REDIS_CACHE_DSN

APP_HTTP_CLIENT_TIMEOUT                          → HTTP_CLIENT_TIMEOUT
APP_HTTP_CLIENT_MAX_DURATION                     → HTTP_CLIENT_MAX_DURATION
APP_HTTP_CLIENT_LOG_LEVEL                        → HTTP_CLIENT_LOG_LEVEL

APP_INTERNAL_OIDC_METADATA_URL                   → INTERNAL_OIDC_METADATA_URL
APP_INTERNAL_OIDC_CLIENT_ID                      → INTERNAL_OIDC_CLIENT_ID
APP_INTERNAL_OIDC_CLIENT_SECRET                  → INTERNAL_OIDC_CLIENT_SECRET
APP_INTERNAL_OIDC_REDIRECT_URI                   → INTERNAL_OIDC_REDIRECT_URI
APP_INTERNAL_OIDC_LEEWAY                         → INTERNAL_OIDC_LEEWAY
APP_INTERNAL_OIDC_CLAIM_NAME                     → INTERNAL_OIDC_CLAIM_NAME
APP_INTERNAL_OIDC_CLAIM_EMAIL                    → INTERNAL_OIDC_CLAIM_EMAIL
APP_INTERNAL_OIDC_CLAIM_GROUPS                   → INTERNAL_OIDC_CLAIM_GROUPS

APP_EXTERNAL_OIDC_METADATA_URL                   → EXTERNAL_OIDC_METADATA_URL
APP_EXTERNAL_OIDC_CLIENT_ID                      → EXTERNAL_OIDC_CLIENT_ID
APP_EXTERNAL_OIDC_CLIENT_SECRET                  → EXTERNAL_OIDC_CLIENT_SECRET
APP_EXTERNAL_OIDC_REDIRECT_URI                   → EXTERNAL_OIDC_REDIRECT_URI
APP_EXTERNAL_OIDC_LEEWAY                         → EXTERNAL_OIDC_LEEWAY
APP_EXTERNAL_OIDC_HASH_SALT                      → EXTERNAL_OIDC_HASH_SALT
APP_EXTERNAL_OIDC_CLAIM_ID                       → EXTERNAL_OIDC_CLAIM_ID
APP_OIDC_CLI_REDIRECT                            → OIDC_CLI_REDIRECT

APP_CALENDAR_API_FEED_SOURCE_LOCATION_ENDPOINT   → CALENDAR_API_FEED_SOURCE_LOCATION_ENDPOINT
APP_CALENDAR_API_FEED_SOURCE_RESOURCE_ENDPOINT   → CALENDAR_API_FEED_SOURCE_RESOURCE_ENDPOINT
APP_CALENDAR_API_FEED_SOURCE_EVENT_ENDPOINT      → CALENDAR_API_FEED_SOURCE_EVENT_ENDPOINT
APP_CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS     → CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS
APP_CALENDAR_API_FEED_SOURCE_EVENT_MODIFIERS     → CALENDAR_API_FEED_SOURCE_EVENT_MODIFIERS
APP_CALENDAR_API_FEED_SOURCE_DATE_FORMAT         → CALENDAR_API_FEED_SOURCE_DATE_FORMAT
APP_CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE       → CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE
APP_CALENDAR_API_FEED_SOURCE_CACHE_EXPIRE_SECONDS → CALENDAR_API_FEED_SOURCE_CACHE_EXPIRE_SECONDS

APP_EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS    → EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS

APP_ADMIN_REJSEPLANEN_APIKEY                     → ADMIN_REJSEPLANEN_APIKEY
APP_ADMIN_SHOW_SCREEN_STATUS                     → ADMIN_SHOW_SCREEN_STATUS
APP_ADMIN_TOUCH_BUTTON_REGIONS                   → ADMIN_TOUCH_BUTTON_REGIONS
APP_ADMIN_LOGIN_METHODS                          → ADMIN_LOGIN_METHODS
APP_ADMIN_ENHANCED_PREVIEW                       → ADMIN_ENHANCED_PREVIEW

APP_CLIENT_LOGIN_CHECK_TIMEOUT                   → CLIENT_LOGIN_CHECK_TIMEOUT
APP_CLIENT_REFRESH_TOKEN_TIMEOUT                 → CLIENT_REFRESH_TOKEN_TIMEOUT
APP_CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT    → CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT
APP_CLIENT_SCHEDULING_INTERVAL                   → CLIENT_SCHEDULING_INTERVAL
APP_CLIENT_PULL_STRATEGY_INTERVAL                → CLIENT_PULL_STRATEGY_INTERVAL
APP_CLIENT_COLOR_SCHEME                          → CLIENT_COLOR_SCHEME
APP_CLIENT_DEBUG                                 → CLIENT_DEBUG
```

The `os2display-docker-server` repo provides `task env:migrate`, which
performs this rename automatically: it reads a 2.x `.env.docker.local`
and writes a 3.x-shaped `.env.local`. Use it as the recommended
migration path:

```shell
# In your os2display-docker-server checkout, on the 3.0 branch:
task env:migrate
task env:diff      # review the result against the canonical example
```

#### 2.2 - Adopt the new operator-facing image-deployment contract

Production deployments now use the GHCR-published images
(`ghcr.io/os2display/display-api-service` and
`ghcr.io/os2display/display-api-service-nginx`) and follow an `env_file:`
pattern in the compose stack.

##### Bootstrap `.env.local` from the image

The image ships a self-documenting `.env` at `/var/www/html/.env` that
lists every Symfony env variable the application consumes, with a
one-line description per variable. Use it as the starting point for
your operator-host `.env.local`:

```shell
docker run --rm ghcr.io/os2display/display-api-service:<tag> \
  cat /var/www/html/.env > .env.local
```

Then edit `.env.local` to set the required values for your environment:

- `APP_ENV=prod`
- `APP_SECRET=<generated-secret>`
- `JWT_PASSPHRASE=<generated-passphrase>`
- `DATABASE_URL=<connection-string>`
- `CORS_ALLOW_ORIGIN=<your-allowed-origin-regex>`
- OIDC provider settings (`INTERNAL_OIDC_*` and/or `EXTERNAL_OIDC_*`)

##### Reference `.env.local` from compose via `env_file:`

```yaml
services:
  api:
    image: ghcr.io/os2display/display-api-service:<tag>
    env_file:
      - .env.local
```

The `os2display-docker-server` compose stack does this for you. See its
`UPGRADE.md` for full operator migration steps.

##### nginx and PHP-FPM runtime tuning

OS-level / runtime knobs are independent of the Symfony env surface and
are passed to their respective images via compose `environment:`:

- nginx: `NGINX_PORT`, `NGINX_FPM_SERVICE`, `NGINX_FPM_PORT`,
  `NGINX_MAX_BODY_SIZE`, `NGINX_SET_REAL_IP_FROM`, `NGINX_WEB_ROOT`
  (defaults in `infrastructure/nginx/Dockerfile`).
- PHP-FPM: `PHP_MEMORY_LIMIT`, `PHP_MAX_EXECUTION_TIME`,
  `PHP_POST_MAX_SIZE`, `PHP_UPLOAD_MAX_FILESIZE`, `PHP_PM_*`,
  `PHP_OPCACHE_*` (consumed by the `itkdev/php8.4-fpm` base image).

#### 3 - Run doctrine migrate

```shell
docker compose exec phpfpm bin/console doctrine:migrations:migrate
```

#### 4 - Run template list command to see status for installed templates

```shell
docker compose exec phpfpm bin/console app:templates:list
```

#### 5 - Run template install for enabling templates

```shell
docker compose exec phpfpm bin/console app:templates:install
```

- Use `--all` option for installing all available templates.
- Use `--update` option for updating existing templates.

#### 6 - Run screen layout list command to see status for installed screen layouts

```shell
docker compose exec phpfpm bin/console app:screen-layouts:list
```

#### 7 - Run screen layout install for enabling screen layouts

```shell
docker compose exec phpfpm bin/console app:screen-layouts:install
```

- Use `--all` option for installing all available templates.
- Use `--update` option for updating existing templates.
- Use `--cleanupRegions` option for cleaning up regions that are no longer connected to a layout.
