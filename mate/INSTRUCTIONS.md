# Project: containerised PHP

This project has no host PHP — all PHP tooling, Mate included, runs inside the `phpfpm` docker compose container,
so the compose stack must be up.

Run Mate through the container:

```sh
docker compose exec -T phpfpm vendor/bin/mate <command>
```

e.g. `mate tools:list` to see the available tools, or `mate discover` after changing Mate extensions.
Never invoke `vendor/bin/mate` (or any `php`/`composer` command) directly on the host.
