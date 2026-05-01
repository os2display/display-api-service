######## Clients [Screen|Admin] build ########
FROM node:24-alpine AS client_app_builder
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0

ENV APP_CLIENT_PATH=/app
WORKDIR ${APP_CLIENT_PATH}

# Copy only necessary files for npm install
COPY --from=repository-root package.json package-lock.json vite.config.js ./

# Install dependencies
RUN npm ci --no-audit --no-fund

# Copy source files needed for build
COPY --from=repository-root assets/ ./assets/
COPY --from=repository-root public/client/ ./public/client/

# Build the application with version info
RUN npm run build


######### API backend build ########
FROM itkdev/php8.4-fpm:alpine AS api_app_builder
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0
ARG APP_RELEASE_TIME=""
ENV APP_CLIENT_PATH=/app \
    APP_API_PATH=/var/www/html

USER root

# Add composer in from the official composer image (also alpine).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR ${APP_API_PATH}

USER deploy

# Copy only composer files first for better layer caching
COPY --chown=deploy:deploy --from=repository-root composer.json composer.lock symfony.lock ${APP_API_PATH}/

# Pre-install composer packages first (better image layer caching) - application code not present so have to be re-run
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative --no-scripts

# Copy application source (needed for build step)
COPY --chown=deploy:deploy --from=repository-root ./ ${APP_API_PATH}/

# Copy javascript build files. This ensures that vite manifest files are availiable when "composer insatll"
# triggers a "cache:clear" enabling the vite bundle to generate cache configuration files
# @see https://symfony-vite.pentatrion.com/guide/performance.html#caching-configuration-files-%F0%9F%8F%83
COPY --chown=deploy:deploy --from=client_app_builder ${APP_CLIENT_PATH}/public/build ./public/build

# Re-run composer install after application code copied to image to complete install
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative

# Write release.json into the public root so the client (assets/shared/release-loader.js)
# can fetch it at /release.json. Shape matches docs/release-example.json.
RUN printf '{"releaseTimestamp": %s, "releaseTime": "%s", "releaseVersion": "%s"}\n' \
      "${APP_RELEASE_TIMESTAMP}" \
      "${APP_RELEASE_TIME}" \
      "${APP_VERSION}" \
      > public/release.json


######## PHP-FPM (API) production image ########
FROM itkdev/php8.4-fpm:alpine
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ENV APP_CLIENT_PATH=/app \
    APP_API_PATH=/var/www/html \
    APP_ENV=prod \
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

# Add composer needed to run optimizations after config is loaded.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Download Prometheus php-fpm export.
COPY --from=hipages/php-fpm_exporter:2.2.0 /php-fpm_exporter /usr/local/bin/php-fpm_exporter

COPY --chmod=0755 docker-entrypoint.sh /usr/local/bin/

USER deploy

WORKDIR ${APP_API_PATH}

# Install the api application.
COPY --chown=deploy:deploy --from=api_app_builder ${APP_API_PATH} .
RUN mkdir -p ./config/secrets

WORKDIR ${APP_API_PATH}

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
