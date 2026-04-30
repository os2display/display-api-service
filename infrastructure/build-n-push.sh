#!/bin/sh
set -eux

APP_VERSION="${APP_VERSION:-develop}"
REGISTRY="${REGISTRY:-ghcr.io/os2display}"
RELEASE_TIMESTAMP="$(date +%s)"
RELEASE_TIME="$(date -u)"

# Set BUILD_LOAD=1 to build for the host platform and load into the local
# docker daemon instead of pushing. Useful for local smoke tests; production
# builds (multi-arch + registry push) require BUILD_LOAD unset or 0.
if [ "${BUILD_LOAD:-0}" = "1" ]; then
  PLATFORMS=""
  OUTPUT="--load"
else
  PLATFORMS="--platform=linux/amd64,linux/arm64"
  OUTPUT="--push"
fi

# API (php-fpm) image. Context is the API infra dir so the Dockerfile picks
# up docker-entrypoint.sh; repository-root is provided as a named build context
# for the COPY --from=repository-root steps.
# shellcheck disable=SC2086
docker buildx build \
  ${PLATFORMS} \
  --pull ${OUTPUT} \
  --build-context repository-root=. \
  --build-arg APP_VERSION="${APP_VERSION}" \
  --build-arg APP_RELEASE_TIMESTAMP="${RELEASE_TIMESTAMP}" \
  --build-arg APP_RELEASE_TIME="${RELEASE_TIME}" \
  --tag "${REGISTRY}/display-api-service:${APP_VERSION}" \
  --file infrastructure/display-api-service/Dockerfile \
  infrastructure/display-api-service

# Nginx image layers on the just-built API image (single source of truth for
# public/), so it has no builder stages of its own. With --push the FROM
# resolves against the registry; with --load the API tag is in the local
# daemon and BuildKit reuses it without a network pull.
# shellcheck disable=SC2086
docker buildx build \
  ${PLATFORMS} \
  --pull ${OUTPUT} \
  --build-arg APP_VERSION="${APP_VERSION}" \
  --build-arg APP_IMAGE="${REGISTRY}/display-api-service" \
  --tag "${REGISTRY}/display-api-service-nginx:${APP_VERSION}" \
  --file infrastructure/nginx/Dockerfile \
  infrastructure/nginx
