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

#### 0 - Convert external templates to custom templates

Instead of loading javascript for templates from possibly external urls we have made the change to only include
templates that are a part of the code. Standard templates are now located in `assets/shared/templates/`.
Custom templates are located in `assets/shared/custom-templates`.

Because of this change, external templates in 2.x will have to be converted to custom templates.
Custom templates are documented in the [README.md#custom-templates](README.md#custom-templates).

The important thing is that the `id` of the template should remain the same when converted to a custom template.

#### 1 - Upgrade the API to the latest version of 2.x

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
