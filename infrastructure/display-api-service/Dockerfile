################################
# Assets build
################################
FROM node:24-alpine AS assets_builder
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ARG APP_VERSION="develop"

WORKDIR /app

# Lockfile + vite config first so the npm install layer caches across
# unrelated source edits.
COPY --from=repository-root package.json package-lock.json vite.config.js ./
RUN npm ci --no-audit --no-fund

# Source for `npm run build`. Vite emits to /app/public/build, which the
# api_app_builder stage copies out.
COPY --from=repository-root assets/ ./assets/
COPY --from=repository-root public/client/ ./public/client/
RUN npm run build


################################
# API backend build
################################
# itkdev/php8.4-fpm publishes two parallel series: `2.x.y` (Ubuntu),
# tracked by `:latest`, and `alpine-1.4.x` (Alpine), tracked by
# `:alpine`. `:alpine` is therefore a moving alias to the newest
# `alpine-1.4.x` — convenient, but not reproducible across time.
# TODO: pin to a specific `alpine-1.4.x` tag (or a digest) once we
# settle on a baseline. Same caveat applies to the final stage below.
FROM itkdev/php8.4-fpm:alpine AS api_app_builder
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0
ARG APP_RELEASE_TIME=""

USER root

# Pinned via the official composer image so the build doesn't depend on
# whatever composer happens to ship in the FPM base.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

USER deploy

# Two-pass composer install:
#   1. Lockfile + composer.json only with --no-scripts. The vendor/ layer
#      then survives unrelated source-code edits.
#   2. After the full source COPY, re-run with scripts to finalise the
#      autoloader and trigger Symfony's cache:clear hook.
COPY --chown=deploy:deploy --from=repository-root composer.json composer.lock symfony.lock ./
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative --no-scripts

COPY --chown=deploy:deploy --from=repository-root ./ ./

# Vite manifest must be in place before the second composer install
# triggers cache:clear, otherwise the vite bundle can't generate its
# cache config files.
# @see https://symfony-vite.pentatrion.com/guide/performance.html#caching-configuration-files-%F0%9F%8F%83
COPY --chown=deploy:deploy --from=assets_builder /app/public/build ./public/build

RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative

# The client polls /release.json to detect when a screen should refresh;
# shape per docs/release-example.json.
RUN printf '{"releaseTimestamp": %s, "releaseTime": "%s", "releaseVersion": "%s"}\n' \
      "${APP_RELEASE_TIMESTAMP}" \
      "${APP_RELEASE_TIME}" \
      "${APP_VERSION}" \
      > public/release.json


################################
# PHP-FPM (API) production image
################################
FROM itkdev/php8.4-fpm:alpine
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ENV APP_ENV=prod \
    PHP_OPCACHE_ENABLED=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_PM_TYPE="dynamic" \
    PHP_PM_MAX_CHILDREN="24" \
    PHP_PM_MAX_REQUESTS="0" \
    PHP_PM_START_SERVERS="5" \
    PHP_PM_MIN_SPARE_SERVERS="5" \
    PHP_PM_MAX_SPARE_SERVERS="10"

USER root

# Needed at container start so the entrypoint can run
# `composer dump-env prod` against the operator's runtime env.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Prometheus exporter for php-fpm pool stats; scraped by ops monitoring.
COPY --from=hipages/php-fpm_exporter:2.2.0 /php-fpm_exporter /usr/local/bin/php-fpm_exporter

COPY --chmod=0755 docker-entrypoint.sh /usr/local/bin/

USER deploy

WORKDIR /var/www/html

COPY --chown=deploy:deploy --from=api_app_builder /var/www/html .

# Mount point for the Symfony secrets vault.
RUN mkdir -p ./config/secrets

# Entrypoint compiles env vars to .env.local.php and warms the Symfony
# cache before exec'ing CMD; CMD stays overridable for ad-hoc commands.
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
