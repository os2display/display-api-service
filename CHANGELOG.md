# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- [#229](https://github.com/os2display/display-api-service/pull/229)
  - Adds options to set paths to component and admin files from path to the json config file.
- [#225](https://github.com/os2display/display-api-service/pull/225)
  - Added ADRs.
- [#215](https://github.com/os2display/display-api-service/pull/215)
  - Added calendar api feed type.
- [#223](https://github.com/os2display/display-api-service/pull/223)
  - Added explicit fixtures to avoid false negatives in the test suite
- [#219](https://github.com/os2display/display-api-service/pull/219)
  - Fixed psalm, test, coding standards and updated api spec.
- [#222](https://github.com/os2display/display-api-service/pull/222)
  - Adds create, update, delete operations to feed-source endpoint.
  - Adds data validation for feed source.

## [2.1.4] - 2025-01-14

- [#230](https://github.com/os2display/display-api-service/pull/230)
  - Adds options to set paths to component and admin files from path to the json config file.

## [2.1.3] - 2024-10-25

- [#220](https://github.com/os2display/display-api-service/pull/220)
  - Fixed issue where saving a screen changed all regions with same ID.

## [2.1.2] - 2024-10-24

- [#213](https://github.com/os2display/display-api-service/pull/213)
  - Set `phpdoc_to_comment` to `false`in `.php-cs-fixer.dist.php` to avoid breaking psalm ignore
  - Add regions and groups to `ScreenInput.php`
  - Add "cascade: persist remove" to PlaylistScreenRegion
  - Save playlist/regions in `ScreenProcessor.php` and in `src/entity/ScreenLayoutRegions` (as an alternative to sending
    multiple requests)
  - Save groups in `ScreenProcessor.php` and in `src/entity/tenant/Screen.php`
  - Update psalm baseline
  - Add regions/playlists and groups to POST screen test
  - `composer update symfony/* --with-dependencies`

## [2.1.1] - 2024-10-23

- [#217](https://github.com/os2display/display-api-service/pull/217)
  - Update composer dependencies to fix redis error

## [2.1.0] - 2024-10-23

- [#214](https://github.com/os2display/display-api-service/pull/214)
  - Updated endSessionUrl to be nullable.

- [#193](https://github.com/os2display/display-api-service/pull/193)
  - Adds support for interactive slides.
  - Adds interactivity for creating quick bookings from a slide through Microsoft Graph.
  - Adds KeyVaultService that can serve key-value entries from the environment for storing secrets.

## [2.0.7] - 2024-08-20

- [#211](https://github.com/os2display/display-api-service/pull/211)
  - Fixed sql error in relations modified listener

## [2.0.6] - 2024-06-28

- [#208](https://github.com/os2display/display-api-service/pull/208)
  - Removed feed items from Notified where image returns 403.
  - Fixed phpunit github actions healthcheck for mariadb.
- [#207](https://github.com/os2display/display-api-service/pull/207)
  - Fixed parameter not set error in (os2display) api container.

## [2.0.5] - 2024-05-21

- [#206](https://github.com/os2display/display-api-service/pull/206)
  - Added support for Notified (Instagram) feed as replacement for SparkleIOFeedType.
  - Deprecated SparkleIOFeedType. (getsparkle.io has shut down)

## [2.0.4] - 2024-04-25

- [#204](https://github.com/os2display/display-api-service/pull/204)
  - Ensured real ip is logged in nginx.
- [#200](https://github.com/os2display/display-api-service/pull/200)
  - Updated oidc internal documentation.
- [#205](https://github.com/os2display/display-api-service/pull/205)
  - Fixed redirecting post requests.

## [2.0.3] - 2024-04-10

- [#203](https://github.com/os2display/display-api-service/pull/203)
  - Changed theme->addLogo() to theme->setLogo().

## [2.0.2] - 2024-04-10

- [#202](https://github.com/os2display/display-api-service/pull/202)
  - Fixed ScreenUser blamable identifier.

## [2.0.1] - 2024-04-09

- [#201](https://github.com/os2display/display-api-service/pull/201)
  - Add /v1 - /v2 redirect controller

## [2.0.0] - 2024-04-09

- [#199](https://github.com/os2display/display-api-service/pull/199)
  - Add doctrine migration to change media references from API 'v1' to API 'v2' in slide content
- [#198](https://github.com/os2display/display-api-service/pull/198)
  - Changed route prefix to v2.
- [#197](https://github.com/os2display/display-api-service/pull/197)
  - Fixed weight issue when assigning slides to playlist.
- [#194](https://github.com/os2display/display-api-service/pull/194) Updated test run documentation and added test for
  `rrule` in playlist.
- Fixed issue with PlaylistSlide transaction.
- Fixed issues with feed following api platform upgrade.
- [#192](https://github.com/os2display/display-api-service/pull/192)
  - Fix env value typo's
- [#191](https://github.com/os2display/display-api-service/pull/191)
  - Add logging for OIDC errors
- [#189](https://github.com/os2display/display-api-service/pull/189)
  - Updated and applied psalm and rector settings
  - Added psalm and rector to PR check on github actions
- [#188](https://github.com/os2display/display-api-service/pull/188)
  - Update build images to PHP 8.3
  - Update to Symfony 6.4 LTS with dependencies
  - Update Github Actions to latest versions
  - Refactor "tenant" injection in repositories
- [#186](https://github.com/os2display/display-api-service/pull/186)
  - Fix for "relations modified" not set correctly on OneToMany relations
- [#185](https://github.com/os2display/display-api-service/pull/185)
  - Disable RelationsModified listener when loading fixtures to optimize performance
- [#184](https://github.com/os2display/display-api-service/pull/184)
  - Added RelationsModifiedTrait to serialization groups.
- [#182](https://github.com/os2display/display-api-service/pull/182)
  - Changed "Theme" api output to have "Logo" embedded to avoid 404 errors when fetching logo from other shared slide w.
  foreign tenant.
- [#181](https://github.com/os2display/display-api-service/pull/181)
  - Update minimum PHP version to 8.2 to support trait constants
  - Add 'relationsModified' timestamps on relevant entities and API resources.
- [#179](https://github.com/os2display/display-api-service/pull/179)
  - Fixed how playlists are added/removed from slides.
- [#178](https://github.com/os2display/display-api-service/pull/178)
  - Fixed issues with objects not being expanded in collections.
- [#176](https://github.com/os2display/display-api-service/pull/176)
  - Fixed issues with objects not being expanded in collections.
- [#175](https://github.com/os2display/display-api-service/pull/175)
  - Fixed issues with objects not being expanded in collections.
- [#174](https://github.com/os2display/display-api-service/pull/174)
  - Update composer dependencies
  - Update `symfony/flex` 1.x -> 2.x
  - Update `vich/uploader-bundle` 1.x -> 2.x
  - Update `debril/feed-io 5.x -> 6.x
  - Enforce strict types
  - Switch from doctrine annotations to attributes
  - Add rector as dev dependency and apply rules
  - Handle doctrine deprecations
- [#173](https://github.com/os2display/display-api-service/pull/173) Upgraded to API Platform 3
- [#172](https://github.com/os2display/display-api-service/pull/172) Linted YAML API resources
- [#171](https://github.com/os2display/display-api-service/pull/171) Fixed slide playlists collection operation.
- [#170](https://github.com/os2display/display-api-service/pull/170) Updated Symfony development packages.
- [#165](https://github.com/os2display/display-api-service/pull/165) Symfony 6.3
- [#162](https://github.com/os2display/display-api-service/pull/162)
  - Adds "external" openid-connect provider.
  - Renamed "oidc" openid-connect provider to "internal".
  - Modifies User to support external user type.
  - Adds command to set user type.
  - Expands api with external user endpoints.
  - Upgrades openid-connect bundle to 3.1 to support multiple providers.
  - Changes php requirement in composer.json to >= 8.1.
  - Removed PHP Upgrade coding standards github actions check.
  - Changed user identifier from email to providerId. Made email nullable. Copied value from email to providerId in
    migration.
- [#161](https://github.com/os2display/display-api-service/pull/161) Fixed non-entity related psalm errors.

## [1.5.0] - 2023-10-26

- [#167](https://github.com/os2display/display-api-service/pull/167)
  - Removed references to non-existing exception.
- [#166](https://github.com/os2display/display-api-service/pull/166)
  - Wrapped feeds in try-catch to avoid throwing errors.
  - Added unpublished flow to EventDatabase feed when occurrence returns 404.
  - Fixed EventDatabase feed poster subscription parameters not being applied when calling getData().
- [#163](https://github.com/os2display/display-api-service/pull/163)
  - Upgraded `itk-dev/openid-connect-bundle` to use code authorization flow. Updated OpenAPI spec accordingly.

## [1.4.0] - 2023-09-14

- [#160](https://github.com/os2display/display-api-service/pull/160)
  - Added app:feed:list-feed-source command. Removed listing from app:feed:remove-feed-source command.
- [#159](https://github.com/os2display/display-api-service/pull/159)
  - Fixed sprintf issue.
- [#158](https://github.com/os2display/display-api-service/pull/158)
  - Added thumbnails for image resources

## [1.3.2] - 2023-07-11

- [#157](https://github.com/os2display/display-api-service/pull/157)
  - Fix question input on create user command

## [1.3.1] - 2023-07-11

- [#156](https://github.com/os2display/display-api-service/pull/156)
  - Fix permissions in create release github action

## [1.3.0] - 2023-07-11

- [#155](https://github.com/os2display/display-api-service/pull/155)
  - Set up separate image builds for itkdev and os2display
- [#154](https://github.com/os2display/display-api-service/pull/154)
  - Updated add user command to ask which tenants user belongs to
- [#151](https://github.com/os2display/display-api-service/pull/151)
  - Fixed feed data provider id issue
- [#150](https://github.com/os2display/display-api-service/pull/150)
  - Update docker build to publish to "os2display" org on docker hub.
  - Update github workflow to latest actions.
- [#148](https://github.com/os2display/display-api-service/pull/148)
  - Updated `EventDatabaseApiFeedType` query ensuring started but not finished events are found.
- [#157](https://github.com/os2display/display-api-service/pull/157)
  - Refactored all feed related classes and services
  - Minor update of composer packages
  - Updated psalm to version 5.x

## [1.2.9] - 2023-06-30

- [#153](https://github.com/os2display/display-api-service/pull/153)
  - Fixed nginx entry script

## [1.2.8] - 2023-05-25

- [#145](https://github.com/os2display/display-api-service/pull/145)
  - Gif mime type possible.

## [1.2.7] - 2023-04-03

- [#143](https://github.com/os2display/display-api-service/pull/143)
  - Fixed token ttl not set correctly for ScreenUsers
- [#142](https://github.com/os2display/display-api-service/pull/142)
  - Make it possible to upload svg in api.

## [1.2.6] - 2023-03-24

- [#141](https://github.com/os2display/display-api-service/pull/141)
  - Readded redis to docker-compose.

## [1.2.5] - 2023-03-16

- [#138](https://github.com/os2display/display-api-service/pull/138)
  - Fixed Tenant and command to allow for empty fallbackImageUrl.
- [#139](https://github.com/os2display/display-api-service/pull/139)
  - Changed from service decoration to event listeners to re-enable setting `tenants` on the response from
    `/v1/authentication/token`.
  - Ensure same response data from both `/v1/authentication/token` and `/v1/authentication/token/refresh`endpoints.
  - Added `user` and `tenants` to JWT payload.

## [1.2.4] - 2023-03-07

- [#133](https://github.com/os2display/display-api-service/pull/133)
  - Adds upload size values to nginx config.
- [#137](https://github.com/os2display/display-api-service/pull/137)
  - Default sorting for templates is by title

## [1.2.3] - 2023-02-14

- [#136](https://github.com/os2display/display-api-service/pull/136)
  - Updated to latest version of github actions
- [#134](https://github.com/os2display/display-api-service/pull/134)
  - Fix bug where `JWT_SCREEN_REFRESH_TOKEN_TTL` value is not used when refresh token is renewed

## [1.2.2] - 2023-02-08

- [#132](https://github.com/os2display/display-api-service/pull/132)
  - Added `RefreshToken` entity to fix migrations error.
- [#135](https://github.com/os2display/display-api-service/pull/135)
  - Updated code styles.

## [1.2.1] - 2023-02-02

- Update composer packages, CVE-2022-24894, CVE-2022-24895

## [1.2.0] - 2023-01-05

- [#130](https://github.com/os2display/display-api-service/pull/130)
  - Added changelog.
  - Added github action to enforce that PRs should always include an update of the changelog.
- [#129](https://github.com/os2display/display-api-service/pull/129)
  - Downgraded to Api Platform 2.6, since 2.7 introduced a change in serialization. Locking to 2.6.*
- [#127](https://github.com/os2display/display-api-service/pull/127)
  - Updated docker setup and actions to PHP 8.1.
  - Updated code style.
- [#128](https://github.com/os2display/display-api-service/pull/128)
  - Added ttl_update: true config option for jwt refresh bundle.
  - Added refresh_token_expiration key to respone body.
- [#124](https://github.com/os2display/display-api-service/pull/124)
  - Created ThemeItemDataProvider instead of
  - ThemeOutputDataTransformer, to make theme accessible in the client on shared slides.
  - Made it possible for editors to view themes and connect them to slides: security: 'is_granted("ROLE_SCREEN") or
is_granted("ROLE_ADMIN") or is_granted("ROLE_EDITOR")'.
- [#126](https://github.com/os2display/display-api-service/pull/126)
  - Added config option for setting token TTL for screen users.
- [#123](https://github.com/os2display/display-api-service/pull/123)
  - Updated fixtures.
- [#125](https://github.com/os2display/display-api-service/pull/125)
  - Changed error handling to not always return empty array even though it is only one resource that reports error.
  - Added error logging.
- [#122](https://github.com/os2display/display-api-service/pull/122)
  - Updated docker setup to match new itkdev base setup.
- [#121](https://github.com/os2display/display-api-service/pull/121)
  - Changed load screen layout command to allow updating existing layouts.

## [1.1.0] - 2022-09-29

- [#120](https://github.com/os2display/display-api-service/pull/120)
  - Fixed path for shared Media.
- [#119](https://github.com/os2display/display-api-service/pull/119)
  - KOBA feed source: Changed naming in resource options. Sorted options.

## [1.0.4] - 2022-09-05

- [#117](https://github.com/os2display/display-api-service/pull/117)
  - Removed screen width and height. Added resolution/orientation.

## [1.0.3] - 2022-09-01

- Changed docker server setup.

## [1.0.2] - 2022-09-01

- Changed docker server setup.

## [1.0.1] - 2022-09-01

- Changed docker server setup.

## [1.0.0] - 2022-05-18

- First release.
