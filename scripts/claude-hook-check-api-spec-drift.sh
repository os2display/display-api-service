#!/bin/sh
# Stop-hook helper: warn when API surface changed without regenerating the OpenAPI spec.
#
# Triggers when working-tree or staged changes include files under
# src/Dto/, src/State/*.php, or config/api_platform/ but no matching change
# to public/api-spec-v2.{yaml,json}. Writes a warning to stderr and exits 0
# (advisory — never blocks).

set -u

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

CHANGED="$(git status --porcelain 2>/dev/null | awk '{print $NF}')"

echo "$CHANGED" | grep -qE '^(src/Dto/|src/State/.+[.]php$|config/api_platform/)' || exit 0

if echo "$CHANGED" | grep -qE '^public/api-spec-v2[.](yaml|json)$'; then
    exit 0
fi

cat >&2 <<'MSG'
WARN: API surface changed (src/Dto, src/State, or config/api_platform/) without
      regenerating public/api-spec-v2.{yaml,json}. Run:

          task generate:api-spec

      If endpoints were added/removed, also run:

          task generate:redux-toolkit-api

      and enhance assets/shared/redux/enhanced-api.ts with the new tag
      invalidations.
MSG

exit 0
