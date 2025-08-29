# OS2Display

## Description

The purpose of OS2Display is to deliver content to information screens.
At the core, is the API that clients can connect to. All data runs through this API.
The project also includes an Admin for creating content and a Client for displaying the content.
The system is browser-based.

Further documentation can be found in the
[https://os2display.github.io/display-docs/](https://os2display.github.io/display-docs/).

## Technologies

The API is written in PHP with Symfony and API Platform as frameworks.

The Admin and Client are written in javascript and React.

## Taskfile

The project ships with a [taskfile](https://taskfile.dev/) for executing common commands.

For a list of commands, run:

```shell
task --list-all
```

## Development setup

To get started with the development setup, run the following task command:

```bash
task site-install
```

or without taskfile:

```bash
docker compose pull
docker compose run --rm node npm install
docker compose up --detach
docker compose exec phpfpm composer install

# Run migrations
docker compose exec phpfpm bin/console doctrine:migrations:migrate

# Load fixtures (Optional)
docker compose exec phpfpm bin/console hautelook:fixtures:load --no-interaction
```

The fixtures have an admin user: <admin@example.com> with the password: "apassword".

The fixtures have an editor user: <editor@example.com> with the password: "apassword".

The fixtures have the image-text template, and two screen layouts: full screen and "two boxes".

## Production setup

In `.env.local` set the following values:

```text
APP_ENV=prod
APP_SECRET=<GENERATE A NEW SECRET>
```

TODO: Add further instructions.

## Coding standards

Before a PR can be merged it has to pass the Github Actions checks. See `.github/workflows`.

Run the coding standards checks:

```shell
task coding-standards:check
```

Apply automatic fixes:

```shell
task coding-standards:apply
```

## Stateless

The API is stateless except `/v2/authentication` routes.
Make sure to set the `CORS_ALLOW_ORIGIN` correctly in `.env.local`.

## OIDC providers

At the present two possible oidc providers are implemented: 'internal' and 'external'.
These work differently.

The internal provider is expected to handle both authentication and authorization.
Any users logging in through the internal will be granted access based on the
tenants/roles provided.

The external provider only handles authentication. A user logging in through the
external provider will not be granted access automatically, but will be challenged
to enter an activation (invite) code to verify access.

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

```curl
Bearer <token>
```

as the api key value. Or by adding an auth header to your requests

```curl
curl --location --request 'GET' \
  'https://display.local.itkdev.dk/v2/layouts?page=1&itemsPerPage=10' \
  --header 'accept: application/ld+json' \
  --header 'Authorization: Bearer <token>'
```

## Tests

### API tests

Run automated tests for the API:

```shell
docker compose exec phpfpm composer test-setup
docker compose exec phpfpm composer test
```

Disable or hide deprecation warnings using the [`SYMFONY_DEPRECATIONS_HELPER` environment
variable](https://symfony.com/doc/current/components/phpunit_bridge.html#configuration), e.g.

```shell
docker compose exec --env SYMFONY_DEPRECATIONS_HELPER=disabled phpfpm composer test
```

### Admin and Client tests

To run tests, use the script:

```shell
./scripts/test.sh
```

This script will stop the node container, build the javascript/css assets, and run tests with playwright,
and starts the node container again.

## API specification and generated code

When the API is changed a new OpenAPI specification should be generated that reflects the changes to the API.

To generate the updated API specification, run the following command:

```shell
docker compose exec phpfpm composer update-api-spec
```

This will generate `public/api-spec-v2.json` and `public/api-spec-v2.yaml`.

This generated API specification is used to generate
[Redux Toolkit RTK Query](https://redux-toolkit.js.org/rtk-query/overview) code for interacting with the API.

To generate the Redux Toolkit RTK Query code, run the following command:

```shell
docker compose exec node npx @rtk-query/codegen-openapi /app/assets/shared/redux/openapi-config.js
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

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/os2display/display-api-service/tags).
