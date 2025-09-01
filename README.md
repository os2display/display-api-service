# OS2Display

## Development

```bash
docker compose pull
docker compose up --detach
docker compose exec phpfpm composer install
docker compose run --rm node npm install

# Run migrations
docker compose exec phpfpm bin/console doctrine:migrations:migrate

# Load fixtures (Optional)
docker compose exec phpfpm bin/console hautelook:fixtures:load --no-interaction
```

The fixtures have an admin user: <admin@example.com> with the password: "apassword".

The fixtures have an editor user: <editor@example.com> with the password: "apassword".

The fixtures have the image-text template, and two screen layouts: full screen and "two boxes".

## Description

TODO

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

## Tests

### API tests

TODO

### Admin and Client tests

To run tests, use the script:

```shell
./scripts/test
```

This script will stop the node container, build the javascript/css assets, and run tests with playwright,
and starts the node container again.

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/os2display/display-api-service/tags).
