# OS2display image build

This folder contains the infrastructure files for building the `os2display/*` images.

Two images are built:

- `os2display/display-api-service`: The web (php-fpm) application image
- `os2display/display-api-service-nginx`: The API (nginx) server image

## Github Actions

Both images are built automatically on push to the `develop` branch, and on tag creation. They are tagged with the
same version as the application.

## GitHub Container Registry

Images are published to <https://github.com/orgs/os2display/packages>.

## Building images locally

`infrastructure/build.sh` builds both images. By default it builds for the host platform and loads the result
into the local docker daemon. Set `PUSH=1` to build multi-arch and push to the registry — opting in keeps a bare
`sh infrastructure/build.sh` from accidentally publishing.

```sh
sh infrastructure/build.sh           # build + load locally
PUSH=1 sh infrastructure/build.sh    # build multi-arch + push to GHCR
```

## Build process

The API (php-fpm) image is built first via a multistage build that performs the `npm` and `composer` build stages
and bakes the result into `/var/www/html`. The nginx image then layers on top of the published API image as a
`FROM` stage and copies the `public/` tree out of it. There is no duplicated builder logic — the `npm`/`composer`
work runs once per CI run.

Because the nginx build depends on the API image, the workflow builds and pushes the API image first; the nginx
step pulls the just-published manifest. For local builds, `build.sh` runs the same order, so the API image is
loaded into the local daemon under the same tag and BuildKit reuses it without a network pull.
