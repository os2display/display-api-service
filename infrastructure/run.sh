#!/bin/sh

APP_VERSION=develop
VERSION=alpha

docker build --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=itkdev/os2display-api-service:${VERSION} --file="display-api-service/Dockerfile" display-api-service
docker build --no-cache --build-arg VERSION=${VERSION} --tag=itkdev/os2display-api-service-nginx:${VERSION} --file="nginx/Dockerfile" nginx

# To build the database you need to dump the fixture database first. This is done in github actions, before build the
# images.
#docker build --no-cache --tag=itkdev/os2display-api-mariadb:${VERSION} --file="display-api-mariadb/Dockerfile" display-api-mariadb

docker push itkdev/os2display-api-service:${VERSION}
docker push itkdev/os2display-api-service-nginx:${VERSION}
#docker push itkdev/os2display-api-mariadb:${VERSION}
