## AI Mate Agent Instructions

This MCP server provides specialized tools for PHP development.
The following extensions are installed and provide MCP tools that you should
prefer over running CLI commands directly.

---

## Project: containerised PHP

This project has no host PHP — all PHP tooling runs inside the `phpfpm` docker compose container.
The Mate MCP server is already wired accordingly in `.mcp.json`
(`docker compose exec -T phpfpm vendor/bin/mate serve`); it requires the compose stack to be up.

When running Mate CLI commands, go through the container:

```sh
task compose -- exec phpfpm vendor/bin/mate <command>
```

e.g. `mate discover` after changing Mate extensions, or `mate mcp:tools:list` to debug.
Never invoke `vendor/bin/mate` (or any `php`/`composer` command) directly on the host.

---

### Server Info

| Instead of...       | Use           |
|---------------------|---------------|
| `php -v`            | `server-info` |
| `php -m`            | `server-info` |
| `uname -s`          | `server-info` |

- Returns PHP version, OS, OS family, and loaded extensions in a single call
