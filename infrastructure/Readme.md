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

Two Taskfile entries wrap the build script:

```sh
task images:build   # build + load into the local docker daemon (host platform)
task images:push    # build multi-arch + push to GHCR (prompts for confirmation)
```

`task images:push` prompts before publishing because the action is non-reversible. The underlying script is
`infrastructure/build.sh`; running it directly defaults to local load. Set `PUSH=1` to push.

## Build process

The two images are built sequentially in the same job; the nginx image layers on top of the just-published API
image as a `FROM` stage. The full pipeline:

1. **Build the assets stage (`assets_builder`).** Runs `npm ci` against the lockfile, then `npm run build`.
   Vite emits the bundled JS/CSS to `/app/public/build/`.
   *Why:* the API image needs the vite manifest in place before its `composer install` triggers
   `cache:clear` — the symfony-vite bundle generates cache config files from the manifest at that point.

2. **Build the API stage (`api_app_builder`).** Two-pass composer install:
   1. `composer install --no-dev --no-scripts` against `composer.lock` only — populates `vendor/` in a layer
      that **stays cached as long as the lockfile is unchanged**, even if all the source code changes.
   2. After the full source tree and the vite manifest are copied in, `composer install` runs again with
      scripts so Symfony's auto-scripts (`cache:clear`, `assets:install`) finalise the autoloader and config
      cache.

   `release.json` is written into `public/` after the second install, with the `APP_VERSION`,
   `APP_RELEASE_TIMESTAMP`, and `APP_RELEASE_TIME` build args.
   *Why:* the screen client polls `/release.json` to detect when it should refresh.

3. **Assemble the production API image.** Copies `vendor/`+source from `api_app_builder`, drops in `composer`
   (needed at container start by the entrypoint to `composer dump-env prod`), the Prometheus php-fpm exporter,
   and `docker-entrypoint.sh`. `ENTRYPOINT` runs `dump-env` + `cache:warmup` against the operator's runtime env
   before exec'ing `CMD ["php-fpm"]`.

4. **Push the API image.** `task images:push` (or the workflow) pushes the multi-arch manifest to
   `ghcr.io/os2display/display-api-service:<tag>` *before* the nginx stage runs.
   *Why:* the nginx image's `FROM` references this published image — multi-arch manifests resolve per
   platform, so the arm64 nginx build copies arm64 contents and amd64 copies amd64 without QEMU re-execution
   of npm/composer.

5. **Build and push the nginx image.** Layers on `ghcr.io/os2display/display-api-service:<tag>` as the `app`
   stage, `COPY`s `/app/public/` out of it, drops in the nginx config, and exposes a `/health`
   endpoint. Pushed to `ghcr.io/os2display/display-api-service-nginx:<tag>`.

For local `task images:build`, steps 4 and 5 run against the local docker daemon instead of the registry —
BuildKit reuses the locally-loaded API image for nginx's `FROM` without a network pull.
