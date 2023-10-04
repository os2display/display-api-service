docker compose exec phpfpm composer test-setup
docker compose exec phpfpm composer test "$@"
