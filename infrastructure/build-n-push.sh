#!/bin/sh

set -eux

APP_VERSION=develop

docker pull itkdev/php8.4-fpm:alpine
docker pull nginxinc/nginx-unprivileged:alpine

docker build --build-context repository-root=.. \
      --platform linux/amd64,linux/arm64 \
      --pull \
      --no-cache \
      --build-arg APP_VERSION=${APP_VERSION} \
      --tag=turegjorup/display-api-service:${APP_VERSION} \
      --file="display-api-service/Dockerfile" display-api-service


docker build --build-context repository-root=.. \
      --platform linux/amd64,linux/arm64 \
      --no-cache \
      --build-arg VERSION=${APP_VERSION} \
      --tag=turegjorup/display-api-service-nginx:${APP_VERSION} \
      --file="nginx/Dockerfile" nginx

docker push os2display/display-api-service:${APP_VERSION}
docker push os2display/display-api-service-nginx:${APP_VERSION}
