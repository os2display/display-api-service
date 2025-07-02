# Test

## Playwright

To test the React apps we use playwright.

### Updating Playwright

It is important that the versions of the playwright container and the library imported in package.json align.

See the `docker-compose.override.yml` playwright entry and the version imported in package.json.

### Dev mode

To run tests locally, there are a few options.

To run from the developer machine:

```shell
BASE_URL="https://display.local.itkdev.dk" npx playwright test template
```

In interactive mode:

```shell
BASE_URL="https://display.local.itkdev.dk" npx playwright test template --ui
```

### Prod mode

Another option is to run the tests on the built javascript assets through the playwright container.
This is the option that runs in Github Actions.

```shell
# Stop the node container, to avoid Vite build dev assets.
docker compose stop node

# Build the assets
docker compose run --rm node npm run build

# Run the test
docker compose run --rm playwright npx playwright test

# To return to vite dev mode, restart the node container.
```
