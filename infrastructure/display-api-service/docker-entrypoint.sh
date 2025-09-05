#!/bin/sh

set -eux

## Run templates with configuration.
/usr/local/bin/confd --onetime --backend env --confdir /etc/confd

## Bump env.local into PHP for better performance.
composer dump-env prod

## Warm-up Symfony cache (with the current configuration).
/var/www/html/bin/console --env=prod cache:warmup

## Set selected composer version. Default version 2.
if [ ! -z "${COMPOSER_VERSION}" ]; then
  if [ "${COMPOSER_VERSION}" = "1" ]; then
    ln -fs /usr/bin/composer1 /home/deploy/bin/composer
  else
    ln -fs /usr/bin/composer2 /home/deploy/bin/composer
  fi
else
  ln -fs /usr/bin/composer2 /home/deploy/bin/composer
fi

exec php-fpm "$@"
