#!/bin/sh
# Validate that the operator-facing configuration surface is fully documented:
#   1. Every %env(...)% read in config/ is declared in .env.
#   2. Every variable in .env, in the nginx example, and in the PHP example
#      has a preceding description comment.
#   3. Every ${VAR} in the nginx template is declared in
#      infrastructure/nginx/env.production.example.
#   4. Every PHP_* env set in the API Dockerfile's production stage is
#      declared in infrastructure/display-api-service/env.production.example.
set -e

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

NGINX_EXAMPLE=infrastructure/nginx/env.production.example
PHP_EXAMPLE=infrastructure/display-api-service/env.production.example
NGINX_TEMPLATE=infrastructure/nginx/etc/templates/default.conf.template
API_DOCKERFILE=infrastructure/display-api-service/Dockerfile

# Vars consumed via %env() but intentionally not part of the .env contract
# (e.g. injected by tooling, not Symfony-defined defaults).
SYMFONY_ALLOW_LIST="VAR_DUMPER_SERVER"

failed=0
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

# Print variables that lack a preceding description comment.
# Recipe markers (###> ... ###) and blank lines reset the "had a comment" flag.
unannotated_vars() {
  awk '
    # ### must be tested before # so recipe markers are not treated as comments.
    /^###/  { last_was_comment = 0; next }
    /^#/    { last_was_comment = 1; next }
    /^$/    { last_was_comment = 0; next }
    /^[A-Z_][A-Z0-9_]*=/ {
      name = $0; sub("=.*", "", name)
      if (!last_was_comment) print name
      last_was_comment = 0
    }
  ' "$1"
}

# Print KEY= keys declared in a dotenv-style file.
declared_keys() {
  grep -E '^[A-Z_][A-Z0-9_]*=' "$1" | cut -d= -f1 | sort -u
}

# --- Check 1: %env() reads in config/ vs declarations in .env -----------------
grep -rhoE '%env\(([a-z]+:)*[A-Z_][A-Z0-9_]*\)%' config/ \
  | sed -E 's/%env\(([a-z]+:)*([A-Z_][A-Z0-9_]*)\)%/\2/' \
  | sort -u > "$tmp/symfony-reads"
declared_keys .env > "$tmp/env-declares"
echo "$SYMFONY_ALLOW_LIST" | tr ' ' '\n' | sort -u > "$tmp/symfony-allow"

undocumented=$(comm -23 "$tmp/symfony-reads" "$tmp/env-declares" \
  | comm -23 - "$tmp/symfony-allow")
if [ -n "$undocumented" ]; then
  echo "FAIL: %env(...)% reads in config/ not declared in .env:"
  echo "$undocumented" | sed 's/^/  /'
  failed=1
fi

# --- Check 2: every variable has a preceding description comment --------------
for f in .env "$NGINX_EXAMPLE" "$PHP_EXAMPLE"; do
  unannotated=$(unannotated_vars "$f")
  if [ -n "$unannotated" ]; then
    echo "FAIL: variables in $f without preceding description:"
    echo "$unannotated" | sed 's/^/  /'
    failed=1
  fi
done

# --- Check 3: nginx template ${VAR} vs nginx example --------------------------
grep -hoE '\$\{[A-Z_][A-Z0-9_]*\}' "$NGINX_TEMPLATE" \
  | sed -E 's/[${}]//g' | sort -u > "$tmp/nginx-template-uses"
declared_keys "$NGINX_EXAMPLE" > "$tmp/nginx-example-declares"

nginx_missing=$(comm -23 "$tmp/nginx-template-uses" "$tmp/nginx-example-declares")
if [ -n "$nginx_missing" ]; then
  echo "FAIL: nginx template variables not declared in $NGINX_EXAMPLE:"
  echo "$nginx_missing" | sed 's/^/  /'
  failed=1
fi

# --- Check 4: PHP_* keys in API Dockerfile prod stage vs PHP example ----------
# Extract the ENV block in the production stage (between the prod FROM and the
# next USER directive), then pick PHP_* keys.
awk '/^FROM itkdev\/php8\.4-fpm:alpine *$/,/^USER /' "$API_DOCKERFILE" \
  | grep -oE 'PHP_[A-Z0-9_]+=' | sed 's/=$//' | sort -u > "$tmp/dockerfile-php"
declared_keys "$PHP_EXAMPLE" > "$tmp/php-example-declares"

php_missing=$(comm -23 "$tmp/dockerfile-php" "$tmp/php-example-declares")
if [ -n "$php_missing" ]; then
  echo "FAIL: PHP_* env set in $API_DOCKERFILE but not declared in $PHP_EXAMPLE:"
  echo "$php_missing" | sed 's/^/  /'
  failed=1
fi

if [ "$failed" -eq 0 ]; then
  echo "PASS: env coverage OK"
fi
exit "$failed"
