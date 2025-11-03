######### API backend ########
FROM itkdev/php8.4-fpm:alpine AS api_app_builder
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ENV APP_API_PATH=/var/www/html

USER root

# Add composer in from the official composer image (also alpine).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR ${APP_API_PATH}

USER deploy

# Copy only composer files first for better layer caching
COPY --chown=deploy:deploy --from=repository-root composer.json composer.lock symfony.lock ${APP_API_PATH}/

# Install composer packages first (better image layer caching)
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative --no-scripts

# Copy application source (needed for build step)
COPY --chown=deploy:deploy --from=repository-root ./ ${APP_API_PATH}/

# Remove frontend apps from api build
RUN rm -rf /assets

# Install composer packages, which requires a HACK as redis is not available (should be removed later on).
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative


####
## Build main application image.
####

FROM itkdev/php8.4-fpm:alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_API_PATH=/var/www/html \
    COMPOSER_VERSION=2

USER root

# Add composer needed to run optimizations after config is loaded.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Download Prometheus php-fpm export.
COPY --from=hipages/php-fpm_exporter:2.2.0 /php-fpm_exporter /usr/local/bin/php-fpm_exporter

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER deploy

# Install the api application.
COPY --chown=deploy:deploy --from=api_app_builder ${APP_API_PATH} ${APP_API_PATH}
RUN mkdir -p ${APP_API_PATH}/config/secrets

WORKDIR ${APP_API_PATH}

CMD [ "docker-entrypoint.sh" ]
