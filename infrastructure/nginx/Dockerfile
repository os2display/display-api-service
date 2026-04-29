######## Clients [Screen|Admin] build ########
FROM node:24-alpine AS client_app_builder
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0

ENV APP_CLIENT_PATH=/app
WORKDIR ${APP_CLIENT_PATH}

# Copy only necessary files for npm install
COPY --from=repository-root package.json package-lock.json vite.config.js ./

# Install dependencies
RUN npm ci --no-audit --no-fund

# Copy source files needed for build
COPY --from=repository-root assets/ ./assets/

# Build the application with version info
RUN npm run build


######### API backend build ########
FROM itkdev/php8.4-fpm:alpine AS api_app_builder
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
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



######## Nginx production image ########
FROM nginxinc/nginx-unprivileged:alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0
ARG UID=101
ARG GID=101

ENV APP_CLIENT_PATH=/app \
    APP_API_PATH=/var/www/html \
    NGINX_PORT=8080 \
    NGINX_SET_REAL_IP_FROM=172.17.0.0/16 \
    NGINX_MAX_BODY_SIZE=10m \
    NGINX_WEB_ROOT=/var/www/html/public

WORKDIR ${APP_API_PATH}

# Create directory and copy built client assets
RUN mkdir -p ${APP_API_PATH}/public
COPY --from=api_app_builder --chown=$UID:0 ${APP_API_PATH}/public ./public

# Copy configuration and entrypoint script
COPY --chown=$UID:0 etc /etc/nginx

# Set proper permissions (files are already owned by $UID:0 from COPY --chown)
RUN chmod -R g+w ${APP_API_PATH}

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:${NGINX_PORT}/health || exit 1

USER $UID

EXPOSE ${NGINX_PORT}

CMD ["nginx", "-g", "daemon off;"]
