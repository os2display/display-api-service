FROM php:8.2-fpm-alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

############# SETUP CONTAINER #############

USER root

ARG APP_VERSION="develop"

ENV APP_PATH=/var/www/html \
    # PHP
    TZ="Europe/Copenhagen" \
    PHP_TIMEZONE="Europe/Copenhagen" \
    PHP_MAX_EXECUTION_TIME="30" \
    PHP_MEMORY_LIMIT="128M" \
    PHP_POST_MAX_SIZE="8M" \
    PHP_UPLOAD_MAX_FILESIZE="2M" \
    PHP_USER="www-data" \
    PHP_GROUP="www-data" \
    PHP_SENDMAIL_PATH='/usr/sbin/sendmail -S host.docker.internal -t -i' \
    # OpCache
    PHP_OPCACHE_ENABLED="1" \
    PHP_OPCACHE_JIT="off" \
    PHP_OPCACHE_REVALIDATE_FREQ=0 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS="1" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="64" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10" \
    # APCU
    PHP_APCU_ENABLED=0 \
    PHP_APCU_ENABLED_CLI=0 \
    PHP_APCU_MEMORY_SIZE="16M" \
    PHP_APCU_SEGMENTS=1 \
    PHP_APCU_PRELOAD_PATH='' \
    # FPM pool
    PHP_PM_TYPE="static" \
    PHP_PM_MAX_CHILDREN="8" \
    PHP_PM_MAX_REQUESTS="0" \
    PHP_PM_START_SERVERS="5" \
    PHP_PM_MIN_SPARE_SERVERS="5" \
    PHP_PM_MAX_SPARE_SERVERS="8" \
    # Other
    PHP_REQUEST_SLOWLOG_TIMEOUT="0" \
    PHP_SLOWLOG="/dev/stderr" \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apk upgrade --no-cache --ignore curl
RUN apk --update add --no-cache \
        libxslt-dev \
        libzip-dev \
        libpng-dev \
        gettext-dev \
        git \
        unzip \
        icu-dev \
        icu-data-full \
        openldap-dev \
        libmcrypt-dev \
        mysql-client \
        libmemcached-libs \
        zlib \
        patch \
        tzdata \
        freetype-dev \
        libjpeg-turbo-dev \
        libjpeg-turbo \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-webp --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        calendar \
        gd \
        gettext \
        intl \
        ldap \
        mysqli \
        opcache \
        pdo_mysql \
        sysvsem \
        soap \
        xsl \
        zip

# Extension that are not available via ext-
RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make zlib-dev libmemcached-dev cyrus-sasl-dev \
    && pecl channel-update pecl.php.net \
    && pecl install redis memcached apcu \
    && docker-php-ext-enable apcu redis memcached \
    && apk del .build-deps

# Install AMQP support
RUN apk --update add --no-cache rabbitmq-c
RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make rabbitmq-c-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp memcached \
    && apk del .build-deps

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Use default PHP production configuration.
RUN mv ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

# # Copy custom PHP configuration.
COPY php/opcache.ini ${PHP_INI_DIR}/conf.d/docker-php-ext-opcache.ini
COPY php/php.ini ${PHP_INI_DIR}/conf.d/zz-php.ini
COPY php/apcu.ini ${PHP_INI_DIR}/conf.d/docker-php-ext-apcu.ini

# Custom FPM configuration.
COPY php/fpm.ini ${PHP_INI_DIR}/../php-fpm.d/zz-fpm-docker.conf

# Add mhsendmail for mailhog
ADD https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 /usr/local/bin/mhsendmail
RUN chmod +x /usr/local/bin/mhsendmail

# Added FPM health check script (https://github.com/renatomefi/php-fpm-healthcheck)
ADD https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# Add git global config
COPY gitconfig /root/.gitconfig

############# SETUP APPLICATION #############

# Move site into the container.
ADD https://github.com/os2display/display-api-service/archive/${APP_VERSION}.tar.gz /tmp/app.tar
RUN tar -zxf /tmp/app.tar --strip-components=1 -C ${APP_PATH} \
    && rm /tmp/app.tar

## Install assets, which requires a HACK as redis is not available (should be removed later on).
RUN APP_ENV=prod composer install --no-dev -o --classmap-authoritative \
    && rm -rf infrastructure \
    && APP_ENV=prod composer clear-cache

# Install the application.
RUN mkdir -p ${APP_PATH}/config/secrets \
    && chown -R www-data:www-data ${APP_PATH}

# Copy configuration.
COPY etc /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

# Copy confd onfiguration.
COPY etc /etc/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR ${APP_PATH}

CMD [ "docker-entrypoint.sh" ]
