# ADR 011 - Structured application logging

Date: 02-06-2026

## Status

Accepted

## Context

Logging in `display-api-service` was a single Monolog channel (`app_http`) used only by
`LoggingHttpClient`, with `fingers_crossed` (action level `error`) writing JSON to
`php://stderr` in production. Log records carried no request, identity, or tenant context,
and ~14 catch sites swallowed exceptions with no log and no rethrow. As a result, several
documented production failures were invisible end-to-end: kiosk black-screens, missing
thumbnails (PR #374), empty interactive booking results, and OIDC/JWT/refresh outcomes.

Operators of single-server deployments frequently cannot run or monitor MariaDB directly
(no shell access to the database, no metrics scraper). The application log is therefore the
only place a database **connection** failure (too-many-connections, connection refused,
server gone away) can surface.

We evaluated enabling PHP persistent database connections to reduce connect overhead and
rejected them: persistent connections are held per PHP-FPM worker, raising the steady-state
connection count toward MariaDB's `max_connections` ceiling (worsening the very
too-many-connections failure we want to observe), they carry dirty session state across
requests, and they produce more "server has gone away" errors as the server closes idle
handles. The DB is a co-located container reached over the Docker network, so the connect
overhead they would save is negligible.

## Decision

1. **Channel splitting.** Declare per-domain Monolog channels (`auth`, `screen`, `media`,
   `feed`, `interactive`, `cache`, and `database`) alongside `app_http`.

2. **Context processors.** Every log record is enriched by Monolog processors with request
   context (request id, route, method), identity (user or screen id, tenant key), W3C trace
   context when present, GDPR-safe client address (truncated), and structured exception
   serialization under a single `exception` context key. Field names follow OpenTelemetry
   semantic conventions.

3. **No silent failures.** Catch blocks must log the exception, rethrow it (optionally
   wrapped), or be explicitly annotated as intentionally silent. This is enforced in CI by
   project-local PHPStan rules (`logging.silentCatch`, `logging.interpolatedLogMessage`,
   `logging.exceptionContextKey`).

4. **Database connection-error logging.** A DBAL driver middleware logs connection
   establishment failures on the `database` channel, classified by the raw driver error
   code, so the failure is visible regardless of whether application code swallows the
   exception and regardless of which SAPI (web, CLI, Messenger) opened the connection.

5. **Persistent connections are not used.** See Context. The application-layer
   connection-error log is the database-failure signal instead.

6. **Output destination is configurable, defaulting to `php://stderr`.** Handlers write to
   a `LOG_PATH` env var (default `php://stderr`). The default suits the Docker image
   deployment, where the runtime captures stderr. Operators who run the repo directly under
   nginx + php-fpm (no container) can set `LOG_PATH` to a file, because php-fpm does not
   capture worker stderr cleanly. Per-channel thresholds are set with `LOG_LEVEL_<CHANNEL>`
   env vars that fall back to a global `LOG_LEVEL`. Shipping logs onward to an aggregator
   (OTel Collector / Loki / Grafana) remains a separate concern in `os2display-docker-server`
   and is not decided here. Records stay JSON regardless of destination.

## Consequences

- Contributors must attach context via the PSR-3 context array (not string interpolation)
  and must never swallow exceptions silently; the PHPStan gate fails the build otherwise.
  The conventions are documented in `docs/logging.md`.
- Operators gain a failure signal for database connection problems without database access,
  at the cost of it being reactive (per-failure events, not "approaching the limit" trends).
- The connection-error middleware covers connection **establishment** only; mid-query drops
  (`2006`/`2013`) are out of its current scope and would require also wrapping the
  connection's execution path.
- Adopting OpenTelemetry semantic conventions for field names keeps logs aligned with the
  OTLP traces the React kiosk already emits, should a collector be introduced later.
- `LOG_PATH` must stay `php://stderr` in the image deployment — pointing it at a
  container-internal file breaks `task logs` and the planned filelog collector. Bare-metal
  operators choosing a file own its rotation (e.g. logrotate) and the php-fpm user's write
  permission to the log directory.
