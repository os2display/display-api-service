######## Client [Screen|Admin] build ########
FROM node:24-alpine AS client_app_builder
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ARG APP_RELEASE_VERSION="develop"
ARG APP_RELEASE_TIMESTAMP=0
ENV APP_CLIENT_PATH=/app

WORKDIR ${APP_CLIENT_PATH}

# Copy only necessary files for npm install
COPY --from=repository-root package.json package-lock.json vite.config.js ${APP_CLIENT_PATH}/

# Install dependencies
RUN npm ci --no-audit --no-fund

# Copy source files needed for build
COPY --from=repository-root assets/ ${APP_CLIENT_PATH}/assets/
COPY --from=repository-root public/client/ ${APP_CLIENT_PATH}/public/client/
COPY --from=repository-root vite.config.js ${APP_CLIENT_PATH}/

# Build the application
RUN npm run build

# Clean up build dependencies and cache
RUN npm cache clean --force && \
    rm -rf node_modules/.cache && \
    rm -rf /root/.npm


######## Nginx build ########
FROM nginxinc/nginx-unprivileged:alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"
ARG UID=101
ARG GID=101

ENV APP_CLIENT_PATH=/app
ENV APP_API_PATH=/var/www/html

USER root

RUN mkdir -p ${APP_API_PATH}/public
COPY --from=client_app_builder ${APP_CLIENT_PATH}/public ${APP_API_PATH}/public

WORKDIR ${APP_API_PATH}

# Copy configuration.
COPY etc /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh \
    && chown -R $UID:0 ${APP_API_PATH} \
    && chmod -R g+w ${APP_API_PATH}

USER $UID

EXPOSE 8080

ENTRYPOINT [ "/docker-entrypoint.sh" ]

CMD ["nginx", "-g", "daemon off;"]
