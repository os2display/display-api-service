# Stop node container to avoid build conflicts.
docker compose stop node
# Build assets.
docker compose run --rm node npm run build
# Run tests.
docker compose run --rm playwright npx playwright test ${TEMPLATE_FILTER}
# Restart node container.
docker compose start node
