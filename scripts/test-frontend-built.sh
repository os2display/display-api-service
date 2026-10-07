#!/bin/sh

# POSIX sh only: the host is assumed to have nothing but Task and docker, and
# `/bin/sh` is dash on Debian/Ubuntu, which rejects bash-only options.
# -e (errexit): Exit immediately if a command exits with a non-zero status.
# -u (nounset): This command will cause the shell to exit if a variable is accessed before it is set.
# -C (noclobber): Prevents accidentally overwriting files with output redirection.
set -eu -C

TEST_PATH="${1:-}"

# Stop node container to avoid build conflicts.
docker compose stop node
# Build assets.
docker compose run --rm node npm run build

# Run tests (with or without a test path)
if [ -n "$TEST_PATH" ]; then
    docker compose run --rm playwright npx playwright test "$TEST_PATH"
else
    docker compose run --rm playwright npx playwright test
fi
# Restart node container.
docker compose start node
