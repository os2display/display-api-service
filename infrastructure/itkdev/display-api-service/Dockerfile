FROM itkdev/php8.3-fpm:alpine AS APP_BUILDER
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ENV APP_PATH=/var/www/html

USER root

# Move site into the container.
ADD https://github.com/os2display/display-api-service/archive/${APP_VERSION}.tar.gz /tmp/app.tar
RUN tar -zxf /tmp/app.tar --strip-components=1 -C ${APP_PATH} \
    && rm /tmp/app.tar

# Add composer in from the official composer image (also alpine).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR ${APP_PATH}

## Install assets, which requires a HACK as redis is not available (should be removed later on).
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative \
    && rm -rf infrastructure \
    && APP_ENV=prod composer clear-cache

####
## Build main application image.
####
FROM itkdev/php8.3-fpm:alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html \
    COMPOSER_VERSION=2

USER root

# Add composer needed to run optimizations after config is loaded.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install the application.
COPY --from=APP_BUILDER ${APP_PATH} ${APP_PATH}
RUN mkdir -p ${APP_PATH}/config/secrets \
    && chown -R deploy:deploy ${APP_PATH}

# Download Prometheus php-fpm export.
COPY --from=hipages/php-fpm_exporter:1.1.1 /php-fpm_exporter /usr/local/bin/php-fpm_exporter

# Copy configuration.
COPY etc /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

# Copy confd onfiguration.
COPY etc /etc/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER deploy

WORKDIR ${APP_PATH}

CMD [ "docker-entrypoint.sh" ]
