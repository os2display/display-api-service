# OS2Display

## Table of Contents

1. [Description](#description)
2. [ADR - Architectural Decision Records](#adr---architectural-decision-records)
3. [Technologies](#technologies)
4. [Versioning](#versioning)
5. [Taskfile](#taskfile)
6. [Development setup](#development-setup)
7. [Production setup](#production-setup)
8. [Coding standards](#coding-standards)
9. [Stateless](#stateless)
10. [OIDC providers](#oidc-providers)
11. [JWT Auth](#jwt-auth)
12. [Test](#test)
13. [API specification and generated code](#api-specification-and-generated-code)
14. [Configuration](#configuration)
15. [Rest API & Relationships](#rest-api--relationships)
16. [Error codes in the Client](#error-codes-in-the-client)
17. [Preview mode in the Client](#preview-mode-in-the-client)
18. [Feeds](#feeds)
19. [Custom Templates](#custom-templates)
20. [Static Analysis](#static-analysis)
21. [Upgrade Guide](#upgrade-guide)
22. [Tenants](#tenants)
23. [Screen layouts](#screen-layouts)
24. [Templates](#templates)

## Description

OS2Display is a browser-based system for delivering content to information screens.

At the core of OS2Display is an API that clients communicate with. All data runs through this API.

It includes an Admin for creating content and a Client for displaying the content.

The structure is that slides are the content element of the system. Each slide is based on a Template with content
added. The slides are gathered into playlists. Playlists are then added to screens.
A screen is the connection between a physical device and the content.

```mermaid
flowchart LR
    A[Admin] <-->B(API)
    B <--> C(Client)
```

Further documentation can be found in the
[https://os2display.github.io/display-docs/](https://os2display.github.io/display-docs/).

## Content Structure

| Component | Description                                                                                                                                                                                                                                                                                                                                                            | Accessible by |
|-----------|:-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:--------------|
| Slide     | A slide is the visible content on a screen.                                                                                                                                                                                                                                                                                                                            | Admin, editor |
| Media     | Media is either images or videos used as content for slides.                                                                                                                                                                                                                                                                                                           | Admin, editor |
| Theme     | A theme has css, that can override the slide css.                                                                                                                                                                                                                                                                                                                      | Admin         |
| Template  | The template is how the slide looks, and which content is on the slide. Templates are accessible to choose on Slides.                                                                                                                                                                                                                                                  | Admin, editor |
| Playlist  | A playlist arranges the order of the slides, and the playlist is scheduled.                                                                                                                                                                                                                                                                                            | Admin, editor |
| Campaign  | A campaign is a playlist, that takes precedence over all other playlists on the screen. If there a multiple campaigns, they are queued. A campaign is either directly attached to a screen, or attached to a group affecting the screens that are members of that group. If a campaign applies to a screen it fills the whole screen, not just a region of the screen. | Admin         |
| Group     | A group is a collection of screens.                                                                                                                                                                                                                                                                                                                                    | Admin         |
| Layout    | A layout consists of different regions, and each region can have a number of playlists connected. A layout is connected to a screen.                                                                                                                                                                                                                                   | Admin         |
| Screen    | A screen is connected to an actual screen, and has a layout with different playlists in.                                                                                                                                                                                                                                                                               | Admin         |

```mermaid
flowchart LR
    Slide -->|1| D[Theme]
    Slide -->|1| E[Template]
    Slide -->|fa:fa-asterisk| F[Media]
```

```mermaid
flowchart LR
    Screen-->|fa:fa-asterisk|Layout
    Layout -->|fa:fa-asterisk|Playlist
    Playlist -->|fa:fa-asterisk|Slide

    Screen-->|fa:fa-asterisk|G[Campaign]
    G -->|fa:fa-asterisk|H[Slide]

    Screen-->|fa:fa-asterisk|Group

    Group-->|fa:fa-asterisk|L[Campaign]
    L -->|fa:fa-asterisk| M[Slide]
```

## ADR - Architectural Decision Records

Architectural decisions are recorded in `docs/adr`.

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/os2display/display-api-service/tags).

## Technologies

The API is written in PHP project, built with [Symfony](https://symfony.com/) and
[API Platform](https://api-platform.com/).

The Admin and Client are written in javascript and [React](https://react.dev/) and built with [Vite](https://vite.dev/).

## Taskfile

The project includes a [taskfile](https://taskfile.dev/) for executing common commands.

See [https://taskfile.dev/docs/installation](https://taskfile.dev/docs/installation) for installation instructions.

If you want to execute the commands without taskfile, look in `taskfile.yml` for the commands that are run.

For a list of commands, run:

```shell
task --list-all
```

## Development setup

Before first installation a JWT Auth keypair should be generated. See [JWT Auth](#jwt-auth).

```shell
docker compose exec phpfpm bin/console lexik:jwt:generate-keypair
```

To get started with the development setup, run the following task command:

```shell
task site-install

# or if you want to load fixtures as well
task site-install-with-fixtures
```

If you want to load fixtures manually, use the command (`--yes` for auto-confirming):

```shell
task fixtures:load --yes
```

The fixtures have an admin user: <admin@example.com> with the password: "apassword".

The fixtures have an editor user: <editor@example.com> with the password: "apassword".

The fixtures have the image-text template, and two screen layouts: "full screen" and "two boxes".

## Production setup

A JWT Auth keypair should be generated. See [JWT Auth](#jwt-auth).

In `.env.local` set the following values:

```text
APP_ENV=prod
APP_SECRET=<GENERATE A NEW SECRET>
```

TODO: Add further production instructions: Build steps, release.json, etc.

Use the `app:update` command to migrate and update templates to latest version:

```shell
docker compose exec phpfpm bin/console app:update --no-interaction
```

## Coding standards

Before a PR can be merged it has to pass the GitHub Actions checks. See `.github/workflows` for workflows that should
pass before a PR will be accepted.

Run the coding standards checks:

```shell
task coding-standards:check
```

Apply automatic fixes:

```shell
task coding-standards:apply
```

## Stateless

The API is stateless except for the `/v2/authentication` routes.

## Authentication

Authentication is achieved through `/v2/authentication/token` for the `/admin`
and through `/v2/authentication/screen` for the `/client`.

## Tenants

Content is connected to a Tenant. A user is in x tenants.
This allows for maintaining multiple content silos in the same installation.

You can add a new tenant:

```shell
docker compose exec phpfpm bin/console app:tenant:add
```

A tenant can be configured with

```shell
docker compose exec phpfpm bin/console app:tenant:configure
```

At the monment, it is possible to configure the fallback image to be shown in the tenant when a screen shows no content.
It is also possible to configure if a tenants should support interactive slides.

## OIDC providers

At the present two possible oidc providers are implemented: 'internal' and 'external'.
These work differently.

The internal provider is expected to handle both authentication and authorization.
Any users logging in through the internal will be granted access based on the
tenants/roles provided.

The external provider only handles authentication. A user logging in through the
external provider will not be granted access automatically, but will be challenged
to enter an activation (invite) code to verify access.

See `docs/feed/openid-connect.md` for environment variables for OpenID Connect configuration.

### Internal

The internal oidc provider gets that user's name, email and tenants from claims.

The claim keys needed are set in the env variables:

- `INTERNAL_OIDC_CLAIM_NAME`
- `INTERNAL_OIDC_CLAIM_EMAIL`
- `INTERNAL_OIDC_CLAIM_GROUPS`

The value of the claim with the name that is defined in the env variable `INTERNAL_OIDC_CLAIM_GROUPS` is mapped to
the user's access to tenants in `App\Security\AzureOidcAuthenticator`. The claim field should consist of an array of
names that should follow the following structure `<TENANT_NAME><ROLE_IN_TENANT>`.
`<ROLE_IN_TENANT>` can be `Admin` or `Redaktoer` (editor).
E.g. `Example1Admin` will map to the tenant with name `Example1` with `ROLE_ADMIN`.
If the tenant does not exist it will be created when the user logs in.

### External

The external oidc provider takes only the claim defined in the env variable
OIDC_EXTERNAL_CLAIM_ID, hashes it and uses this hash as providerId for the user.
When a user logs in with this provider, it is initially not in any tenant.
To be added to a tenant the user has to use an activation code a
ROLE_EXTERNAL_USER_ADMIN has created.

## JWT Auth

To authenticate against the API locally you must generate a private/public key pair:

```shell
docker compose exec phpfpm bin/console lexik:jwt:generate-keypair
```

Then create a local test user if needed:

```shell
docker compose exec phpfpm bin/console app:user:add
```

You can now obtain a token by sending a `POST` request to the
`/v2/authentication/token` endpoint:

```curl
curl --location --request 'POST' \
  'https://display.local.itkdev.dk/v2/authentication/token' \
  --header 'accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "email": "editor@example.com",
  "password": "apassword"
}'
```

Either on the command line or through the OpenApi docs at `/docs`

You can use the token either by clicking "Authorize" in the docs and entering

```text
Bearer <token>
```

as the api key value. Or by adding an auth header to your requests

```curl
curl --location --request 'GET' \
  'https://display.local.itkdev.dk/v2/layouts?page=1&itemsPerPage=10' \
  --header 'accept: application/ld+json' \
  --header 'Authorization: Bearer <token>'
```

## Test

### API tests - PHPUnit

We use PHPUnit for API tests:

```shell
task test:api
```

### Frontend tests - Playwright

To test the React apps we use playwright.

#### Updating Playwright

It is important that the versions of the playwright container and the library imported in package.json align.

See the `docker-compose.override.yml` playwright entry and the version imported in package.json.

#### Testing on the built files

This project includes a test script that handles building assets, running
Playwright tests, and stops and starts the node container. This script tests the
*built* files. This is the approach the GitHub Action uses.

```shell
task test:frontend-built
```

or

```shell
./scripts/test {TEST-PATH}
```

TEST-PATH is optional, and is the specific test file or directory to run like
`admin`/`client`/`template` or a specific file, e.g. `admin-app.spec.js`. If
TEST-PATH is omitted, all tests will run.

#### Testing on local machine

To run test from the local machine, there are a few options.

```shell
task test:frontend-local
```

In interactive mode:

```shell
task test:frontend-local-ui
```

### Manual tests

A manual test guide is included with the project: [docs/test-guide/test-guide.md](docs/test-guide/test-guide.md).

## API specification and generated code

When the API is changed a new OpenAPI specification should be generated that reflects the changes to the API.

To generate the updated API specification, run the following command:

```shell
task generate:api-spec
```

This will generate `public/api-spec-v2.json` and `public/api-spec-v2.yaml`.

This generated API specification is used to generate
[Redux Toolkit RTK Query](https://redux-toolkit.js.org/rtk-query/overview) code for interacting with the API.

To generate the Redux Toolkit RTK Query code, run the following command:

```shell
task generate:redux-toolkit-api
```

This will generate `assets/shared/redux/generated-api.ts`. This generated code is enhanced by the custom file
`assets/shared/redux/enhanced-api.ts`.

### Important

If new endpoints are added to the API, `assets/shared/redux/enhanced-api.ts` should be modified to reflect changes in
Redux-Toolkit cache invalidation and new hooks should be added.

See
[https://redux-toolkit.js.org/rtk-query/usage/code-generation](https://redux-toolkit.js.org/rtk-query/usage/code-generation)
for information about the code generation.

## Configuration

Configuration of the project should be added to `.env.local`. Default values are set in `.env`.

```dotenv
###> App ###
DEFAULT_DATE_FORMAT='Y-m-d\TH:i:s.v\Z'
ACTIVATION_CODE_EXPIRE_INTERVAL=P2D
KEY_VAULT_SOURCE=ENVIRONMENT
KEY_VAULT_JSON="{}"
TRACK_SCREEN_INFO=false
TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS=300
###< App ###
```

- DEFAULT_DATE_FORMAT: The default format of serialized dates.
- ACTIVATION_CODE_EXPIRE_INTERVAL: Specifies how long an external user activation code should live.
  The format of the interval should follow <https://www.php.net/manual/en/dateinterval.construct.php>.

  **Default**: 2 days.
- KEY_VAULT_SOURCE: Source of key-value pair for `src/Service/KeyVaultService`. Atm. "ENVIRONMENT" is the only
  option.
- KEY_VAULT_JSON: A json object formatted as a string. Contains key-value pairs that can be accessed by through
  `src/Service/KeyVaultService`.
- EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS: What should the expire be for cache entries in EventDatabaseApiV2FeedType?
- TRACK_SCREEN_INFO: Should screen info be tracked (true|false)?
- TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS: How often (seconds) should the screen info be tracked from API requests?

### Admin configuration

Will be exposed through the `/config/admin` route.

```dotenv
###> Admin configuration ###
### Will be exposed through the /config/admin route.
ADMIN_REJSEPLANEN_APIKEY=
ADMIN_SHOW_SCREEN_STATUS=false
ADMIN_TOUCH_BUTTON_REGIONS=false
ADMIN_LOGIN_METHODS='[{"type":"username-password","enabled":true,"provider":"username-password","label":""}]'
ADMIN_ENHANCED_PREVIEW=false
###< Admin configuration ###
```

- ADMIN_REJSEPLANEN_APIKEY: An API key accessing Rejseplanen API used for Travel template.
  See [https://labs.rejseplanen.dk/](https://labs.rejseplanen.dk/) for information about acquiring an API key.

  **Default**: Not set.
- ADMIN_SHOW_SCREEN_STATUS: Should the status of the screen be shown in the Admin (true|false)?

  **Default**: Disabled.
- ADMIN_TOUCH_BUTTON_REGIONS: Should the option of setting a button name for a slide be enabled in the Admin?
  This option is used by the Client if a region is configured to be a "touch-buttons" region.

  **Default**: Disabled.
- ADMIN_LOGIN_METHODS: Which login methods should be displayed in the admin (array of objects as json string)?

  Available types: "oidc" | "username-password".

  ```json
  {
      "type": "oidc",
      "provider": "internal",
      "label": "Button text",
      "icon": "faCity"
  }
  ```

  - provider: "internal" | "external". See "OIDC providers" for a description of OIDC providers.
  - label: Button text. Defaults to "Ekstern" for "external" provider and "Medarbejder" for "internal" provider.
  - icon: Name of the fontawesome icon to use for the button or "mitID" for MitID logo.

  ```json
    {
      "type": "username-password",
      "provider": "username-password",
      "label": ""
    }
  ```

  - provider: "username-password"
  - label: Label for the username password login section

  **Default**: Username and password login option is enabled.
- ADMIN_ENHANCED_PREVIEW: Should the enhanced preview mode be active (true|false)? When enabled, previews will be
  handled by iFraming in the Client app. This will allow the option of previewing playlists and screens.
  If disabled, only slides can be previewed. This will be with the "live" method. This preview is not as precise.
  See [Preview mode in the Client](#preview-mode-in-the-client).

  **Default**: Disabled.

### Client configuration

Will be exposed through the `/config/client` route.

```dotenv
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

- CLIENT_LOGIN_CHECK_TIMEOUT: How often (milliseconds) should the screen check for status when it is not logged in, and
  waiting for being activated in the administration.

  **Default**: 20 s.
- CLIENT_REFRESH_TOKEN_TIMEOUT: How often (milliseconds) should it be checked whether the token needs to be refreshed?

  **Default**: 30 s.
- CLIENT_REFRESH_TOKEN_TIMEOUT: How often (milliseconds) should it be checked whether the token needs to be refreshed?

  **Default**: 60 s.
- CLIENT_SCHEDULING_INTERVAL: How often (milliseconds) should the scheduling be run for the logged in screen?

  **Default**: 60 s.
- CLIENT_PULL_STRATEGY_INTERVAL: How often (milliseconds) should data be pulled from the API?

  **Default**: 1 m. and 30 s.
- CLIENT_COLOR_SCHEME: Which colour scheme should be enabled? Should be a json object as string.
  This is used to signal how changes to darkmode are handled.
  Options are:
  - Not set - will use the browsers prefers-color-scheme setting.
  - '{"type":"library","lat":56.0,"lng":10.0}' - In this case the change to darkmode is handled with a library that
    activates darkmode according to sunrise/sunset of the location given by the longitude/latitude (lat/lng).

  **Default**: Library mode with a lat/lng set in Denmark.
- CLIENT_DEBUG: Should the Client be in debug mode (true|false). When not in debug mode the mouse pointer is hidden.

  **Default**: Disabled.

### Other configuration options

- See `docs/configuration/openid-connect.md` for configuration of OpenID Connect.
- See `docs/configuration/calendar-api-feed.md` for configuration of CalenderApiFeedType.

#### Event Database Api V2 Feed Type

```dotenv
###> Event Database Api V2 Feed Source ###
EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS=300
###< Event Database Api V2 Feed Source ###
```

- EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS: What should the expiration be for cache entries in
  EventDatabaseApiV2FeedType?

## Rest API & Relationships

To avoid embedding all relations in REST representations but still allow the clients to minimize the amount of API calls
they have to make all endpoints that have relations also has a `relationsModified` field:

```json
  "@id": "/v2/screens/000XB4RQW418KK14AJ054W1FN2",
  ...
  "relationsModified": {
      "campaigns": "cf9bb7d5fd04743dd21b5e3361db7eed575258e0",
      "layout": "4dc925b9043b9d151607328ab2d022610583777f",
      "regions": "278df93a0dc5309e0db357177352072d86da0d29",
      "inScreenGroups": "bf0d49f6af71ac74da140e32243f3950219bb29c"
  }
```

The checksums are based on `id`, `version` and `relationsModified` fields of the entity under that key in the
relationship tree. This ensures that any change in the bottom of the tree will propagate as changed checksums up the
tree.

Updating `relationsModified` is handled in a `postFlush` event listener `App\EventListener\RelationsModifiedAtListener`.
The listener will execute a series of raw SQL statements starting from the bottom of the tree and progressing up.

### Partial Class Diagram

For reference a partial class diagram to illustrate the relevant relationships.

```mermaid
classDiagram
    class `Screen`
    class `ScreenCampaign`
    class `ScreenGroup`
    class `ScreenGroupCampaign`
    class `ScreenLayout`
    class `ScreenLayoutRegions`
    class `PlaylistScreenRegion`
    class `Playlist`
    class `Schedule`
    class `PlaylistSlide`
    class `Slide`
    class `Template`
    class `Theme`
    class `Media`
    class `Feed`
    class `FeedSource`
    Screen "1..*" -- "0..n" ScreenGroup
    Screen "0..*" -- "1" ScreenLayout
    Screen "1" -- "0..*" ScreenCampaign
    ScreenLayout "1" -- "1..n" ScreenLayoutRegions
    ScreenGroup "1" -- "1..n" ScreenGroupCampaign
    Screen "1" -- "1..n" PlaylistScreenRegion
    ScreenLayoutRegions "1" -- "1..n" PlaylistScreenRegion
    ScreenCampaign "0..n" -- "1" Playlist
    PlaylistScreenRegion "0..n" -- "1" Playlist
    ScreenGroupCampaign "0..n" -- "1" Playlist
    Playlist "1" -- "0..n" Schedule
    Playlist "1" -- "0..n" PlaylistSlide
    PlaylistSlide "0..n" -- "1" Slide
    Slide "0..n" -- "1" Template
    Slide "0..n" -- "1" Theme
    Theme "0..n" -- "0..1" Media : Has logo
    Slide "0..n" -- "0..n" Media : Has media
    Slide "0..1" -- "0..1" Feed
    Feed "0..n" -- "1" FeedSource
```

## Online check for Client

If the client does not have internet when starting, it cannot load the assets needed for the Client.
The `public/client/online-check` has been added to handle this.
The folder contains an `index.html`, that checks connectivity before redirecting to `/client`.
If this index.html is cached in the browser the online check page can load without internet.

To use this, set the starting path of the Client to `/client/online-check`.

## Error codes in the Client

The Client at `/client` can display the following error codes:

- ER101: API returns 401. Token could not be refreshed. This could be caused by logging out in the admin.
- ER102: Token could not be refreshed in normal refresh token loop.
- ER103: Token refresh aborted, refresh token, iat and/or exp not set.
- ER104: Release file could not be loaded.
- ER105: Token is expired.
- ER106: Token is valid but should have been refreshed.
- ER201: Error loading slide template.

## Preview mode in the Client

The Client can be started in preview mode by setting the following url parameters:

```text
preview=<screen|playlist|slide>
preview-id=<id of entity to preview>
preview-token=<token for accessing data>
preview-tenant=<tenant id>
```

The preview will use the token and tenant for accessing the data from the api.

This feature is used in the Admin for displaying previews of slides, playlists and screens.

## Screen status

Screen status consists of 2 elements. Tracking latest request from a screen client.
This data is collected and exposed through the API.

The other part is in the admin where the data can be exposed to the user.

To enable screen status information tracking and showing this information in the admin requires setting these .env
variables:

```dotenv
# Enable tracking screen information.
TRACK_SCREEN_INFO=true
# Data will only be updated with this frequency
TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS=300
# Enable screen information in Admin.
ADMIN_SHOW_SCREEN_STATUS=true
```

### List view

In the list view of screens, there is a column called "Status".

This column shows the status of the connection of a "screen" in the administration and an
actual "machine" running the screen data.

This status can be:

- "+ Tilkobl": The screen is not connected to a machine.
- ✓ (green):  The machine is connected and running the latest code.
- i (yellow circle): The machine is not running the newest released code.
- ! (red triangle): The machine has not called the API within the last hour or the access token is expired.

### Screen edit view

In the screen edit view, the "Tilkobling" section shows the status of the connection between the
screen entity and a machine running the screen data.

The status can be:

- "Skærmen er tilkoblet" (green): The machine is connected and running the latest code.
- "Skærmen kører ikke seneste udgivelse" (yellow circle): The machine is not running the newest released code.
- "Skærmen har ikke kommunikeret i mere end en time" (red triangle): The machine has not called the API the latest hour.

Furthermore, the section "Tilkobling" will show the following data:

```text
* Seneste kommunikation: 14/12 2024 11:35
* Version: 1.0.9
* Kodeudgivelsestidspunkt: 17/6 2024 17:26
```

This shows when the latest communication has occured, what client version the machine is running,
and the time of client code release.

## Feeds

"Feeds" in OS2display are external data sources that can provide up-to-data to slides. The idea is that if you can set
up a slide based on a feed and publish it. The Screen Client will then fetch new data from the feed whenever the Slide
is shown on screen.

The simplest example is a classic RSS news feed. You can set up a slide based on the RSS slide template, configure the
RSS source URL, and whenever the slide is on screen it will show the latest entries from the RSS feed.

This means that administrators can set up slides and playlists that stays up to date automatically.

### Architecture

The "Feed" architecture is designed to enable both generic and custom feed types. To enable this all feed based screen
templates are designed to support a given "feed output model". These are normalized data sets from a given feed type.

Each feed implementation defines which output model it supports. Thereby multiple feed implementations can support the
same output model. This is done to enable decoupling of the screen templates from the feed implementation.

For example:

- If you have a news source that is not a RSS feed you can implement a "FeedSource" that fetches data from your source
  then normalizes the data and outputs it as the RSS output model. When setting up RSS slides this feed source can then
  be selected as the source for the slide.
- OS2display has calendar templates that can show bookings or meetings. To show data from your specific calendar or
  booking system you can implement a "FeedSource" that fetches booking data from your source and normalizes it to match
  the calendar output model.

## Create a new FeedType

To implement a new FeedType, create a class that implements `src/Feed/FeedTypeInterface`.

## List installed Feed Sources

```shell
docker compose exec phpfpm bin/console app:feed:list-feed-source
```

## Create a Feed Source

To create a feed source use the following command:

```shell
docker compose exec phpfpm bin/console app:feed:create-feed-source
```

This will start an interactive session where the secrets and configuration for the feed source can be set.

To override an existing feed source, use the ulid in the command above, eg.:

```shell
docker compose exec phpfpm bin/console app:feed:create-feed-source 01FYRMSGGHG4VXS3Z0WACG6BX8
```

## Remove a Feed Source

```shell
docker compose exec phpfpm bin/console app:feed:remove-feed-source 01FYRMSGGHG4VXS3Z0WACG6BX8
```

## Themes

It is possible to create themes that can apply to select templates. See `/admin/themes` in the Admin.

The theme css has to follow som rules. See [docs/themes/themes.md](docs/themes/themes.md) for instructions on writing
custom themes.

## Templates

A list of installed and available templates can be seen with:

```shell
docker compose exec phpfpm bin/console app:templates:list
```

Templates can be installed with the

```shell
docker compose exec phpfpm bin/console app:templates:install <TEMPLATE_ULID>
```

or all templates with

```shell
docker compose exec phpfpm bin/console app:templates:install --all
```

To remove a template:

```shell
docker compose exec phpfpm bin/console app:templates:remove <TEMPLATE_ULID>
```

When running in dev mode, the route `/template` can be visited to preview how templates are rendered with different
fixtures.

### Video

When using the video template the video will not autoplay in the `/client` unless the autoplay flag is enabled in the
browser configuration. For Chrome see:

[https://developer.chrome.com/blog/autoplay#developer_switches](https://developer.chrome.com/blog/autoplay#developer_switches)

## Custom Templates

OS2Display ships with some standard templates. These are located in `assets/shared/templates`.

It is possible to include custom templates in your installation.

### Location

Custom templates should be placed in the folder `assets/shared/custom-templates/`.
This folder is in `.gitignore` so the contents will not be added to the git repository.

How you populate this folder with your custom templates is up to you:

- A git repository with root in the `assets/shared/custom-templates/` folder.
- A symlink from another folder.
- Maintaining a fork of the display repository.
- ...

### Files

The following files are required for a custom template:

- `custom-template-name.jsx` - A javascript module for the template.
- `custom-template-name.json` - A configuration file for the template.

Replace `custom-template-name` with a unique name for the template.

#### custom-template-name.jsx

The `.jsx` should expose the following functions:

- id() - The ULID of the template. Generate a ULID for your custom template.
- config() - Should contain the following keys: id (as above), title (the titel displayed in the admin), options,
  adminForm.
- renderSlide(slide, run, slideDone) - Should return the JSX for the template.
  - slide: The slide data from the API.
  - run: A date string that will be set when the slide should start executing.
  - slideDone: A function that is called when the slide is done.

For an example of a custom template see `assets/shared/custom-templates-example/`.

The slide is responsible for signaling that it is done executing.
This is done by calling the slideDone() function. If the slide should just run for X milliseconds then you can use the
`useBaseSlideExecution` hook to handle this. See the example for this approach.

##### Admin Form

To get content into the slide the config.adminForm field should be set. This should be an array of objects with the
following attributes:

- input: The type of the input field. Supported types:
  - input: Regular html5 input.
  - header: Headline.
  - header-h3: Sub-headline.
  - select: Select.
  - checkbox: Checkbox.
  - rich-text-input: Text field with support for rich text html.
  - image: Upload image(s) or select from media archive.
  - video: Upload video(s) or select from media archive.
  - file: Upload file(s) or select from media archive.
  - duration: Slide duration field.
  - contacts: Create contacts entries
  - feed: Configure a feed for the slide.
  - table: Create table content.
  - textarea: Textarea.
- name: A name, should be unique. This is the field in slide.content what will be set.
- type: text, number or email, for input type.
- label: Label for the input
- helpText: A helptext for the input
- required: Whether it is required data
- formGroupClasses: For styling, bootstrap, e.g. mb-3
- options: An array of options {name,id} for the select

Look at the existing templates in `assets/shared/templates/` for examples.

In production, these custom templates need to be built together with the normal templates with the `npm run build`
command.

### Contributing template

If you think the template could be used by other, consider contributing the template to the project as a pull request.

#### Guide for contributing templates

- Fork the `os2display/display` repository.
- Move your custom template files (the .json and .jsx files and other required files) from the
  `assets/shared/custom-templates/` folder to the `assets/shared/templates/` folder.
- Create a PR to `os2display/display` repository.

## Screen Layouts

A screen layout is a setting that defines how a screen is divided into different regions.
A layout consists of a grid.

The grid regions are created from the number of rows and columns selected for the given layout. The regions are named

`[a-z][aa-zz][aaa-zzz]`

Core layouts are stored in `assets/shared/screen-layouts` and custom layouts can be placed in
`assets/shared/custom-screen-layouts`.

To see status of screen layouts:

```shell
docker compose exec phpfpm bin/console app:screen-layouts:list
```

To install a layout:

```shell
docker compose exec phpfpm bin/console app:screen-layouts:install <SCREEN_LAYOUT_ULID>
```

or all with

```shell
docker compose exec phpfpm bin/console app:screen-layouts:install --all
```

To remove a layout:

```shell
docker compose exec phpfpm bin/console app:screen-layouts:remove <SCREEN_LAYOUT_ULID>
```

### Touch regions in layouts

A region can be rendered as buttons. In this scenario each slide that is present in a region is added as a button that
can be opened in full screen. It will close when the slide has run or if the user presses the close button.

To make a layout region into a touch button region, add the following to the region in the layout `.json` file:

```text
"type": "touch-buttons"
```

## Static analysis

[Psalm](https://psalm.dev/) is used for static analysis:

```shell
task code-analysis
```

We use [a baseline file](https://psalm.dev/docs/running_psalm/dealing_with_code_issues/#using-a-baseline-file) for Psalm
([`psalm-baseline.xml`](psalm-baseline.xml)).

Run this command to update the baseline file:

```shell
task psalm:update-baseline
```

Psalm [error level](https://psalm.dev/docs/running_psalm/error_levels/) is set to level 2.

## Upgrade Guide

See [UPGRADE.md](UPGRADE.md) for upgrade guides.
