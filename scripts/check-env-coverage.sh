#!/bin/sh
# Validate that .env documents every Symfony env variable consumed in config/:
#   1. Every %env(...)% read in config/ is declared in .env.
#   2. Every variable in .env has a preceding description comment.
set -e

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

# Vars consumed via %env() but intentionally not part of the .env contract
# (e.g. injected by tooling, not Symfony-defined defaults).
ALLOW_LIST="VAR_DUMPER_SERVER"

failed=0
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

# Check 1 — every %env(...)% read in config/ is declared in .env
grep -rhoE '%env\(([a-z]+:)*[A-Z_][A-Z0-9_]*\)%' config/ \
  | sed -E 's/%env\(([a-z]+:)*([A-Z_][A-Z0-9_]*)\)%/\2/' \
  | sort -u > "$tmp/symfony-reads"
grep -E '^[A-Z_][A-Z0-9_]*=' .env | cut -d= -f1 | sort -u > "$tmp/env-declares"
echo "$ALLOW_LIST" | tr ' ' '\n' | sort -u > "$tmp/allow"

undocumented=$(comm -23 "$tmp/symfony-reads" "$tmp/env-declares" | comm -23 - "$tmp/allow")
if [ -n "$undocumented" ]; then
  echo "FAIL: %env(...)% reads in config/ not declared in .env:"
  echo "$undocumented" | sed 's/^/  /'
  failed=1
fi

# Check 2 — every variable in .env has a preceding description comment
unannotated=$(awk '
  # Order matters: ### must be tested before # so recipe markers are not
  # treated as descriptions.
  /^###/  { last_was_comment = 0; next }
  /^#/    { last_was_comment = 1; next }
  /^$/    { last_was_comment = 0; next }
  /^[A-Z_][A-Z0-9_]*=/ {
    name = $0; sub("=.*", "", name)
    if (!last_was_comment) print name
    last_was_comment = 0
  }
' .env)
if [ -n "$unannotated" ]; then
  echo "FAIL: variables in .env without preceding description:"
  echo "$unannotated" | sed 's/^/  /'
  failed=1
fi

if [ "$failed" -eq 0 ]; then
  echo "PASS: env coverage OK"
fi
exit "$failed"
