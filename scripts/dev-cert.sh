#!/usr/bin/env sh
#
# Generate a self-signed certificate for the bundled dev traefik.
# Writes traefik/ssl/dev.{crt,key} covering COMPOSE_DOMAIN as the primary
# CN plus a handful of common local-dev fallbacks as SubjectAltNames.
#
# Usage: scripts/dev-cert.sh           (no-op if dev.crt already exists)
#        FORCE=1 scripts/dev-cert.sh   (overwrite)
#
# NOT FOR PRODUCTION. RSA-2048, SHA-256, 365 days, untrusted CA. Browsers
# will show a warning the first time you accept it.
#
# Requires: docker. Uses alpine/openssl in a transient container so no
# openssl is needed on the host.

set -eu
# `pipefail` is not supported by all `/bin/sh` implementations (e.g. dash).
# Enable it when available so the script can still be run by `sh` from Task.
(set -o pipefail) 2>/dev/null && set -o pipefail || true

CERT_DIR="traefik/ssl"
CERT_FILE="$CERT_DIR/dev.crt"
KEY_FILE="$CERT_DIR/dev.key"

# Pull COMPOSE_DOMAIN from .env / .env.local so the cert matches the host
# the dev will actually browse to. Defensive sourcing — chained Task calls
# may have stale exported env if .env was just rewritten.
set -a
# shellcheck disable=SC1091
[ -f .env ]       && . ./.env
# shellcheck disable=SC1091
[ -f .env.local ] && . ./.env.local
set +a

# No bundled traefik in this dev's setup (itkdev devs use a host-level
# traefik and set COMPOSE_PROFILES= in .env.local). Don't generate a
# cert nothing will use.
case ",${COMPOSE_PROFILES:-}," in
  *,traefik,*) ;;
  *)
    echo "Bundled traefik not in COMPOSE_PROFILES; skipping dev cert."
    exit 0
    ;;
esac

APP_DOMAIN="${COMPOSE_DOMAIN:-display.localhost}"

if [ -f "$CERT_FILE" ] && [ "${FORCE:-0}" != "1" ]; then
  echo "$CERT_FILE already exists. Set FORCE=1 to overwrite."
  exit 0
fi

mkdir -p "$CERT_DIR"

docker run --rm -v "$PWD/$CERT_DIR:/out" alpine/openssl \
  req -x509 -newkey rsa:2048 -nodes \
      -keyout /out/dev.key \
      -out    /out/dev.crt \
      -days   365 \
      -subj   "/CN=${APP_DOMAIN}" \
      -addext "subjectAltName=DNS:${APP_DOMAIN},DNS:node-${APP_DOMAIN},DNS:localhost,IP:127.0.0.1"

echo "Generated:"
echo "  $CERT_FILE   (covers: ${APP_DOMAIN}, node-${APP_DOMAIN}, localhost, 127.0.0.1)"
echo "  $KEY_FILE"
echo
echo "Restart traefik to pick up the new cert:  docker compose restart traefik"
