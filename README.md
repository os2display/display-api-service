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

The fixtures have an admin user: admin@example.com with the password: "apassword".

The fixtures have an editor user: editor@example.com with the password: "apassword".

The fixtures have the image-text template, and two screen layouts: full screen and "two boxes".

## Description

TODO

## Documentation

## Tests

### API tests

TODO

### Admin / Client tests

To run tests, use the script:

```shell
./scripts/test.sh
```

This script will stop the node container, build the javascript/css assets, and run tests with playwright,
and starts the node container again.

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/os2display/display-api-service/tags).
