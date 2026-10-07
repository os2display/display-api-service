#!/bin/sh
# Stop-hook helper: warn when a slide template's .jsx and .json drift apart.
#
# A slide template is two files in assets/shared/templates/<name>.{jsx,json}.
# Pushing only one half half-renders the admin form. This script checks the
# working tree for any base name where exactly one of the pair is present,
# writes a warning to stderr, and exits 0 (advisory — never blocks).

set -u

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

DIR="assets/shared/templates"
[ -d "$DIR" ] || exit 0

ORPHAN_JSX=""
ORPHAN_JSON=""

# .jsx without .json
for f in "$DIR"/*.jsx; do
    [ -e "$f" ] || continue
    base=$(basename "$f" .jsx)
    if [ ! -f "$DIR/$base.json" ]; then
        ORPHAN_JSX="$ORPHAN_JSX $base"
    fi
done

# .json without .jsx
for f in "$DIR"/*.json; do
    [ -e "$f" ] || continue
    base=$(basename "$f" .json)
    if [ ! -f "$DIR/$base.jsx" ]; then
        ORPHAN_JSON="$ORPHAN_JSON $base"
    fi
done

if [ -z "$ORPHAN_JSX" ] && [ -z "$ORPHAN_JSON" ]; then
    exit 0
fi

{
    echo "WARN: slide template pair(s) out of sync in $DIR/"
    if [ -n "$ORPHAN_JSX" ]; then
        echo "      Missing .json for:"
        for name in $ORPHAN_JSX; do echo "        - $name"; done
    fi
    if [ -n "$ORPHAN_JSON" ]; then
        echo "      Missing .jsx for:"
        for name in $ORPHAN_JSON; do echo "        - $name"; done
    fi
    echo "      A slide template must ship both halves — see add-slide-template skill."
} >&2

exit 0
