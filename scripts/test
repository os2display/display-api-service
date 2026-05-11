#!/usr/bin/env bash

# -o errexit: Exit immediately if a command exits with a non-zero status.
# -o errtrace: Ensures that the ERR trap is also triggered when the error occurs inside a function or a subshell. (https://stackoverflow.com/questions/25378845/what-does-set-o-errtrace-do-in-a-shell-script)
# -o noclobber: Prevents accidentally overwriting files with output redirection.
# -o nounset: This command will cause the shell to exit if a variable is accessed before it is set.
# -o pipefail: Ensures that a pipeline return the exit status of the command that first fails.
set -o errexit -o errtrace -o noclobber -o nounset -o pipefail

TEST_PATH="${1:-}"

# Stop node container to avoid build conflicts.
docker compose stop node
# Build assets.
docker compose run --rm node npm run build

# Run tests (with or without a test path)
if [[ -n "$TEST_PATH" ]]; then
    docker compose run --rm playwright npx playwright test "$TEST_PATH"
else
    docker compose run --rm playwright npx playwright test
fi
# Restart node container.
docker compose start node
