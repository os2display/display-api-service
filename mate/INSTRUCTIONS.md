# Project: containerised PHP

This project has no host PHP — all PHP tooling runs inside the `phpfpm` docker compose container.
The Mate MCP server is already wired accordingly in `.mcp.json`
(`docker compose exec -T phpfpm vendor/bin/mate serve`); it requires the compose stack to be up.

When running Mate CLI commands, go through the container:

```sh
task compose -- exec phpfpm vendor/bin/mate <command>
```

e.g. `mate discover` after changing Mate extensions, or `mate mcp:tools:list` to debug.
Never invoke `vendor/bin/mate` (or any `php`/`composer` command) directly on the host.
