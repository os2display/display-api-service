#!/bin/sh

set -eux

## Run templates with configuration.
/usr/local/bin/confd --onetime --backend env --confdir /etc/confd

## Bump env.local into PHP for better performance.
composer dump-env prod

## Warm-up Symfony cache (with the current configuration).
/var/www/html/bin/console --env=prod cache:warmup

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
  set -- php-fpm "$@"
fi

## Start the PHP FPM process.
echo "Starting PHP 8.3 FPM"

exec "$@"
