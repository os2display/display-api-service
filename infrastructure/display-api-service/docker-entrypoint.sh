#!/bin/sh

set -eu

## Dump dotenv files into PHP for better performance.
## @see https://symfony.com/doc/6.4/configuration.html#configuring-environment-variables-in-production
composer dump-env prod

## Warm-up Symfony cache (with the current configuration).
/var/www/html/bin/console --env=prod cache:warmup

exec "$@"
