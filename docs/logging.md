# Logging

This service emits **structured JSON logs** to `php://stderr`. Every record is enriched
with request, identity and trace context, and serialised so it can be ingested by a log
aggregator later without changes here. The decisions behind this are recorded in
[ADR 011](adr/011-structured-logging.md).

The conventions below are **enforced in CI** by project-local PHPStan rules
(`logging.silentCatch`, `logging.interpolatedLogMessage`, `logging.exceptionContextKey`).
A build fails if you break them.

## Channels

Inject a channel-specific logger instead of the generic one, so records can be routed and
filtered per domain. Bind the channel in `config/services.yaml`:

```yaml
App\Service\FeedService:
    arguments:
        $logger: '@monolog.logger.feed'
```

| Channel | Use for |
|---|---|
| `outbound_http` | Outbound HTTP client calls (`LoggingHttpClient`). Named to stay clear of Symfony's built-in `http_client` channel, whose native (request-only) logging is silenced with a `NullLogger`. |
| `auth` | Authentication and authorization outcomes (login, logout, JWT/OIDC). |
| `screen` | Screen-client binding, screen authentication, screen status. |
| `media` | Media upload, thumbnailing, storage. |
| `feed` | Feed fetching/parsing (`FeedService`, feed types). |
| `interactive` | Interactive slides (booking, instant book). |
| `cache` | **Not an application channel** — Symfony's built-in cache-adapter channel. Given a dedicated handler so cache-adapter (Redis) backend failures surface instead of being dropped by the `fingers_crossed` gate. No application code writes to it. |
| `database` | Database connection failures (see below). |

## Level guidance

| Level | When |
|---|---|
| `debug` | Developer detail; verbose, off in production action buffering. |
| `info` | Normal, noteworthy events (auth success, batch completed). |
| `notice` | Normal but significant (config fallback applied). |
| `warning` | Something recoverable went wrong (auth failure, flapping feed, malformed payload). |
| `error` | An operation failed and a user/screen is affected. |
| `critical` | A subsystem is down (database connection pressure/unreachable). |

## Message and context rules

1. **The message is a static string.** Never interpolate variables into it.

   ```php
   // ❌ flagged by logging.interpolatedLogMessage
   $logger->warning("Feed {$source->getId()} failed");

   // ✅ static message, data in context
   $logger->warning('Feed fetch failed', ['feed_source_id' => (string) $source->getId()]);
   ```

   PSR-3 `{placeholder}` braces resolved from the context array are fine.

2. **Exceptions go under the `exception` context key — never any other key, never
   interpolated.**

   ```php
   // ❌ flagged by logging.exceptionContextKey
   $logger->error('Booking failed', ['error' => $e]);

   // ✅
   $logger->error('Booking failed', ['exception' => $e]);
   ```

   `ExceptionContextProcessor` turns the `\Throwable` into a structured array
   (`class`, `message`, `code`, `file`, `line`, bounded `previous` chain). No raw
   multi-line stack-trace string is emitted at info level.

3. **Never swallow an exception silently.** A `catch` must log it (under `exception`),
   rethrow it (optionally wrapped), or be explicitly annotated when the silence is
   intentional:

   ```php
   try {
       $optional = $this->parse($payload);
   } catch (\JsonException $e) {
       // @phpstan-ignore logging.silentCatch (optional metadata; absence is expected)
   }
   ```

   This is enforced by `logging.silentCatch`.

## Field names (OpenTelemetry semantic conventions)

Field names follow [OpenTelemetry semantic conventions](https://opentelemetry.io/docs/specs/semconv/)
as strictly as the framework allows, so the logs stay forward-compatible with an OTel
collector should one be introduced:

| Field | Meaning |
|---|---|
| `request_id` | Per-request id (inbound `X-Request-Id` or minted). |
| `http.request.method` | HTTP method. |
| `http.route` | Matched route's **path template**, e.g. `/v2/screens/{id}` — id-free and low-cardinality, with the API Platform `.{_format}` suffix stripped. **Set only when a route matched** (per OTel guidance), and **never** the concrete id-bearing URL. |
| `url.path` | The concrete request path, **including** ids (e.g. `/v2/screens/01HXYZ…`). This is the field that carries the real path; `http.route` is its templated, id-free counterpart. |
| `client.address` | Client address — **truncated** for users/anonymous callers, kept **in full** for screen clients (see redaction). |
| `enduser.id` | Back-office user identifier. |
| `screen.id` | Screen id (for screen-token requests). |
| `tenant.key` | Active tenant key. |
| `trace_id` / `span_id` | W3C trace context, when a `traceparent` header is present. |

Strict OTel notes: `http.route` is the **path template** the server matched, not the
Symfony route *name* and not the concrete path; it is omitted entirely (rather than set to
an empty/placeholder value) when no route matched. `enduser.id` and `screen.id` are mutually
exclusive. Fields that don't apply to a record are absent, never `null`.

### `extra` (ambient request) vs `context` (the event)

The processors above add the **inbound** request's attributes to every record's `extra`
(`http.request.method`, `http.route`, `url.path`, `client.address`, …). A log *call* may
put its own subject's attributes in `context` using the same OTel names — for example the
outbound HTTP client (`outbound_http` channel) logs `http.request.method` / `url.full` /
`http.response.status_code` / `http.client.request.duration` for the call it just made.
So a record for an outbound call can carry `extra.http.request.method` (the inbound API
request) **and** `context.http.request.method`
(the outbound call). This is intentional and mirrors OTel's server-span vs client-span split:
`extra` is the ambient request the worker is serving; `context` is the event being logged.
The two stay in separate bags, so there is no key collision.

## Redaction guarantees

`SensitiveDataProcessor` runs last and is a backstop, not a license to log secrets:

- `client.address` is **truncated** for back-office users and anonymous callers — IPv4
  drops the last octet (`203.0.113.5` → `203.0.113.0`); IPv6 keeps the `/48` and zeroes the
  rest; an address that does not parse as an IP is replaced with `[redacted]`.
  **Screen-client (kiosk) requests are exempt and keep their full IP.** A kiosk is an
  unattended public display, not a personal device, so it falls outside GDPR, and the full
  address helps identify a specific screen. A record is treated as a screen client when it
  carries `screen.id` (set upstream by `RequestContextProcessor`).
- Any key whose name contains `password`, `passwd`, `secret`, `authorization`, `api_key`,
  `apikey`, `token`, `credential` or `bearer` is replaced with `[redacted]`, at any depth
  in `context`/`extra`.

`SensitiveDataProcessor` matches on the context **key name only** — it never inspects
*values*. A secret carried inside a value under an innocuous key (most commonly a URL with
`?api_key=…` in its query string) is therefore **not** caught by the backstop and must be
sanitised at the source. The outbound HTTP client does this: `LoggingHttpClient` redacts the
query string of `url.full` wholesale (`https://host/path?[redacted]`) and drops any userinfo
(`user:pass@`) before logging, so credentials in an outbound URL never reach the log.

Still: do not put credentials or token strings into log context in the first place.
