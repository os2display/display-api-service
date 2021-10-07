#!/bin/sh

APP_VERSION=develop
VERSION=alpha

docker build --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=itkdev/os2display-api-service:${VERSION} --file="display-api-service/Dockerfile" display-api-service
docker build --no-cache --build-arg VERSION=${VERSION} --tag=itkdev/os2display-api-service-nginx:${VERSION} --file="nginx/Dockerfile" nginx

docker push itkdev/os2display-api-service:${VERSION}
docker push itkdev/os2display-api-service-nginx:${VERSION}
