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

A script is provided to build the images locally: `build-n-push.sh`

## Build process

Both images uses multistage builds, with the first two stages being identical. And the final stage is optimized for
the specific image.

This is done because both images requires files from both the `npm` and `composer` build stages. And while having a
shared build stage when building locally is possible, it's not feasible when building on Github Actions.
