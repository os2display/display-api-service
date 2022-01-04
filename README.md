# DisplayApi

## OpenAPI specification
The OpenAPI specification is commited to this repo as `public/api-spec-v1.yaml`

A CI check will compare the current API implementation to the spec. If they
are different the check will fail.

If a PR makes _planned_ changes to the spec, the commited file must be updated:

```shell
docker compose exec phpfpm composer update-api-spec
```

If these are _breaking_ changes the API version must be changes accordingly.

## Development Setup

A `docker-compose.yml` file with a PHP 7.4 image is included in this project.
To install the dependencies you can run

```shell
docker compose up -d
docker compose exec phpfpm composer install
```

## JWT Auth

To authenticate against the API locally you must generate a private/public key pair:

```shell
docker compose exec phpfpm bin/console lexik:jwt:generate-keypair
```

Then create a local test user if needed:

```shell
docker compose exec phpfpm bin/console app:user:add
```

You can now obtain a token by sending af `POST` request to the
`/authentication_token` endpoint:

```curl
curl -X 'POST' \
  'http://displayapiservice.local.itkdev.dk/authentication_token' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "email": "test@test.com",
  "password": "testtest"
}'
```

Either on the command line or through the OpenApi docs at `/docs`

You can use the token either by clicking "Authorize" in the docs and entering

```curl
Bearer <token>
```

as the api key value. Or by adding an auth header to your requests

```curl
curl -X 'GET' \
  'http://displayapiservice.local.itkdev.dk/v1/layouts?page=1&itemsPerPage=10' \
  -H 'accept: application/ld+json' \
  -H 'Authorization: Bearer <token>'
```

### Psalm static analysis

[Psalm](https://psalm.dev/) is used for static analysis. To run
psalm do

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/psalm
```

Psalm [error level](https://psalm.dev/docs/running_psalm/error_levels/) is set
to level 2.

### Composer normalizer

[Composer normalize](https://github.com/ergebnis/composer-normalize) is used for
formatting `composer.json`

```shell
docker compose exec phpfpm composer normalize
```

### Check Coding Standard

The following command let you test that the code follows
the coding standard for the project.

* PHP files [PHP Coding Standards Fixer](https://cs.symfony.com/)

    ```shell
    docker compose exec phpfpm composer check-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:latest install
    docker run -v ${PWD}:/app itkdev/yarn:latest check-coding-standards
    ```

### Apply Coding Standards

To attempt to automatically fix coding style issues

* PHP files [PHP Coding Standards Fixer](https://cs.symfony.com/)

    ```sh
    docker compose exec phpfpm composer apply-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:latest install
    docker run -v ${PWD}:/app itkdev/yarn:latest apply-coding-standards
    ```

## CI

Github Actions are used to run the test suite and code style checks on all PRs.

If you wish to test against the jobs locally you can install [act](https://github.com/nektos/act).
Then do:

```shell
act -P ubuntu-latest=shivammathur/node:latest pull_request
```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/openid-connect/tags).
