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

Rename the following .env variables in `.env.local`:

- From `APP_DEFAULT_DATE_FORMAT` to `DEFAULT_DATE_FORMAT`
- From `APP_ACTIVATION_CODE_EXPIRE_INTERVAL` to `ACTIVATION_CODE_EXPIRE_INTERVAL`
- From `APP_KEY_VAULT_SOURCE` to `KEY_VAULT_SOURCE`
- From `APP_KEY_VAULT_JSON` to `KEY_VAULT_JSON`

#### 3 - Consolidate Doctrine migrations

3.0 ships a single consolidated migration that represents the end-of-2.8 schema. The 25 historical
2.x migrations have been removed from the repository.

Because every upgrading database already matches that consolidated schema (via the 25 migrations it
ran while on 2.x), there is nothing for `doctrine:migrations:migrate` to do — and running it would
fail because of the orphaned version entries. Use `doctrine:migrations:rollup` instead, which
truncates the `doctrine_migration_versions` table and inserts a single row marking the consolidated
migration as already executed:

```shell
# Confirm the database is at the latest 2.8.x state before rolling up.
# All 25 historical versions should appear as "migrated" / "available".
docker compose exec phpfpm bin/console doctrine:migrations:status

# Replace the 25 historical version entries with the single consolidated entry.
# This does not run any SQL — it only rewrites the version-tracking table.
docker compose exec phpfpm bin/console doctrine:migrations:rollup --no-interaction
```

> **Prerequisite:** the database must be on the final 2.8.x release with every 2.x migration
> applied. If `doctrine:migrations:status` (run while still on 2.8.x) reports any pending
> migrations, run `doctrine:migrations:migrate` on 2.8.x first, then upgrade to 3.0 and continue
> here.

Fresh installs (no prior 2.x database) skip the rollup and run
`doctrine:migrations:migrate` instead — it executes the single consolidated migration and brings
the schema up in one step.

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
