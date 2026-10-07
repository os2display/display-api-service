---
name: add-feed-type
description: Add a new FeedType to display-api-service — a PHP class implementing App\Feed\FeedTypeInterface that pulls data from an external source, normalizes it into one of the shared output models (RSS/CALENDAR/INSTAGRAM/POSTER/BRND_BOOKING), and describes its own admin form and configuration. Use when the user asks to add a new feed source, integrate an external API into the slide-feed system, or extend the renderer with a new feed shape.
disable-model-invocation: true
---

# Add a new FeedType

A FeedType is a PHP class in `src/Feed/` that:

1. Knows how to talk to one external data source.
2. **Normalizes** that data into one of the shared **output models** (`RSS_OUTPUT`, `CALENDAR_OUTPUT`, `INSTAGRAM_OUTPUT`, `POSTER_OUTPUT`, `BRND_BOOKING_OUTPUT`) that slide templates render against.
3. Describes its own admin UI (`getAdminFormOptions`, `getConfigOptions`) and config/secret requirements (`getRequiredSecrets`, `getRequiredConfiguration`, `getSchema`).

Decoupling templates from feed implementations via the output-model contract is the whole point — see README "Feeds" + ADR 005.

## TL;DR checklist

- [ ] Pick the output model that fits your source (or add a new one — heavy, requires template updates).
- [ ] Create `src/Feed/MyFeedType.php` implementing `App\Feed\FeedTypeInterface`.
- [ ] Implement the 7 interface methods (see below).
- [ ] If env-bound scalars are needed, declare in `config/services.yaml` and add to `.env` + `.env.test`.
- [ ] **Don't** manually tag the service — `_instanceof` auto-tags via the interface.
- [ ] Add a unit test under `tests/Feed/MyFeedTypeTest.php`.
- [ ] **Add the class to `FeedServiceTest::testGetFeedTypes()`** — regression guard.
- [ ] Add a doc under `docs/feed/my-feed-type.md` (env vars, secrets shape, sample data).
- [ ] CHANGELOG entry under unreleased.
- [ ] Smoke-test end-to-end via the admin.

## 1. Decide what you're building

| Question | Why |
|---|---|
| Which **output model**? | Determines which slide templates can render the feed. Must match an existing `FeedOutputModels::*` constant unless adding a new template too. |
| What **secrets** does the source need? | Per-tenant, stored encrypted in `FeedSource.secrets` (JSON column). Set `exposeValue => true` on non-sensitive ones so the UI can display them. |
| What **per-feed config** does an editor pick? | URL, item count, taxonomy filters — lands in `Feed.configuration` (JSON column). |
| Does the admin need **dynamic dropdowns** (e.g. "list my calendars")? | If yes, implement `getConfigOptions()` and use `multiselect-from-endpoint` in `getAdminFormOptions()`. |
| What **caching** is needed? | `FeedService` already caches `getData()` per-feed. Only add lower-level caching for expensive shared lookups (see `CalendarApiFeedType::$calendarApiCache`). |

If the output model genuinely doesn't fit anything existing, add a constant to `src/Feed/FeedOutputModels.php` with a docblock example of the data shape — but that also requires frontend template work (templates filter `FeedSource` by `supportedFeedOutputType`, so a new model is invisible to existing templates).

## 2. Skeleton

```php
<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MyFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::RSS_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,    // only if you need dynamic admin dropdowns
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        // env-var-bound scalars wired via config/services.yaml (§5)
    ) {}

    public function getData(Feed $feed): array { /* §3.7 */ }
    public function getAdminFormOptions(FeedSource $feedSource): array { /* §3.5 */ }
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array { /* §3.6 */ }
    public function getRequiredSecrets(): array { /* §3.2 */ }
    public function getRequiredConfiguration(): array { /* §3.3 */ }
    public function getSupportedFeedOutputType(): string { return self::SUPPORTED_FEED_TYPE; }
    public function getSchema(): array { /* §3.4 */ }
}
```

**Service registration is automatic** — `config/services.yaml` has `_instanceof: App\Feed\FeedTypeInterface: { tags: [app.feed.feed_type] }`, and `src/` is autoloaded. Implementing the interface is enough.

## 3. The 7 methods

Authoritative signatures live in `src/Feed/FeedTypeInterface.php`. Canonical behaviour for each:

### 3.1 `getSupportedFeedOutputType(): string`

Return one of `FeedOutputModels::*`. **Frozen for the source's lifetime** — changing it on an existing `FeedSource` orphans all linked slides.

### 3.2 `getRequiredSecrets(): array`

Declare keys for `FeedSource.secrets`. `'exposeValue' => true` makes a key readable by the frontend.

```php
return [
    'token' => ['type' => 'string'],
    'locations' => [
        'type' => 'string_array',
        'options' => $this->getLocationOptions(),
        'exposeValue' => true,
    ],
];
```

### 3.3 `getRequiredConfiguration(): array`

Keys that must exist in `Feed.configuration` before `getData()` runs. Documentation only — enforce in `getData()`.

### 3.4 `getSchema(): array`

JSON Schema (draft-04) validated by `FeedSourceProcessor` on POST/PUT. Start permissive.

```php
return [
    '$schema' => 'http://json-schema.org/draft-04/schema#',
    'type' => 'object',
    'properties' => ['token' => ['type' => 'string']],
    'required' => ['token'],
];
```

Gotcha: PUT with empty `secrets` payload skips validation (intentional — allows editing title without re-sending secrets).

### 3.5 `getAdminFormOptions(FeedSource $feedSource): array`

Admin form descriptor. Common inputs:

| `input` | Use for |
|---|---|
| `input` + `type: 'url' \| 'number' \| 'text'` | Plain text/number. See `RssFeedType`. |
| `multiselect-from-endpoint` | Dropdown populated from `getConfigOptions()`. See `NotifiedFeedType`. |
| `checkbox-options` | Static checkbox group. See `CalendarApiFeedType` modifiers. |

Dynamic-dropdown pattern:

```php
$endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'feeds');
return [[
    'key' => 'my-feeds-selector',
    'input' => 'multiselect-from-endpoint',
    'endpoint' => $endpoint,
    'name' => 'feeds',
    'label' => 'Vælg feed',
    'formGroupClasses' => 'col-md-6 mb-3',
]];
```

`name` becomes the key under `Feed.configuration`. The second arg to `getFeedSourceConfigUrl()` is the `$name` your `getConfigOptions()` dispatches on.

### 3.6 `getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array`

Called by `FeedSourceConfigGetController` for dynamic dropdowns. Return `[{id, title, value}, …]` or `null` for unknown `$name`.

**Swallow exceptions here** — the admin form should degrade gracefully while an editor is still typing credentials. Log + return `null`/`[]`.

```php
try {
    if ('feeds' === $name) {
        $data = $this->callRemoteApi($feedSource->getSecrets()['token'] ?? '');
        return array_map(fn (array $item) => [
            'id' => Ulid::generate(),
            'title' => $item['name'] ?? '',
            'value' => $item['id'] ?? '',
        ], $data);
    }
} catch (\Throwable $t) {
    $this->logger->error('{code}: {message}', ['code' => $t->getCode(), 'message' => $t->getMessage()]);
}
return null;
```

### 3.7 `getData(Feed $feed): array` — the hot path

Rules (these are the most common bugs):

1. **Validate required secrets/config first** — throw `\RuntimeException` with class-prefixed message if missing.
2. **Let exceptions bubble.** `FeedService::getData()` wraps the call and caches an empty result for **30 s** on failure (`ERROR_CACHE_TTL_SECONDS`). Catching-and-returning-`[]` yourself caches empty with the **full feed TTL** (often hours) — silent breakage.
3. **Log at the catch site, then rethrow:**

   ```php
   try { /* … */ }
   catch (\Throwable $t) {
       $this->logger->error('MyFeedType: Failed for feed {feedId}: {message}', [
           'feedId' => $feed->getId(),
           'message' => $t->getMessage(),
           'exception' => $t,
       ]);
       throw $t;
   }
   ```

4. **Don't add per-call caching around the whole `getData()`** — `FeedService` already caches per-feed and honours `configuration['cache_expire']` as a per-feed TTL override. Cache lower-level shared lookups separately if needed.

## 4. Output data contract

Your returned array must match `FeedOutputModels.php` docblocks for the chosen model. Summary:

| Model | Constant | Shape (abridged) |
|---|---|---|
| RSS | `RSS_OUTPUT` | `{title, entries: [{title, lastModified, content, author?}]}` or `[{title, lastModified, content}]` (see `RssFeedType` vs `ColiboFeedType`). |
| Calendar | `CALENDAR_OUTPUT` | `[{id, title, startTime, endTime, resourceTitle, resourceId}]` — Unix timestamps. |
| Instagram | `INSTAGRAM_OUTPUT` | `[{textMarkup, mediaUrl, videoUrl, username, createdTime}]`. |
| Poster | `POSTER_OUTPUT` | See `EventDatabaseApiV2FeedType` for the concrete shape. |
| Brnd booking | `BRND_BOOKING_OUTPUT` | `[{activity, area, bookingBy, bookingcode, startTime, endTime, …}]`. |

Missing source fields → fill with sensible defaults or drop the entry. Templates should never crash on `null`.

## 5. Wire configuration

### Env-var-bound scalars → `config/services.yaml`

```yaml
App\Feed\MyFeedType:
    arguments:
        $endpoint: '%env(string:MY_FEED_TYPE_ENDPOINT)%'
        $cacheExpireSeconds: '%env(int:MY_FEED_TYPE_CACHE_EXPIRE_SECONDS)%'
```

Add the vars to **both** `.env` (with safe defaults / empty strings) and `.env.test` — the env-coverage check (`scripts/check-env-coverage.sh`) and the test kernel both depend on it.

### Per-tenant data → `FeedSource.secrets`, not env vars

Tokens, tenant ids, account keys — anything per-tenant. Only system-wide wiring (shared endpoint URLs, global cache tuning) goes in env vars.

### Dedicated cache pool (if needed)

Declare in `config/packages/cache.yaml` and inject by name. **Don't** reuse `$feedsCache` for intermediate lookups — its keys are feed ids, you'll collide with `FeedService`.

## 6. Reference implementations to copy from

| Your source looks like… | Start from |
|---|---|
| Plain RSS/Atom URL | `src/Feed/RssFeedType.php` |
| REST API with bearer token + dynamic dropdowns | `src/Feed/NotifiedFeedType.php` |
| Multiple endpoints with heavy caching + transformations | `src/Feed/CalendarApiFeedType.php` |
| External system with its own SDK/client | `src/Feed/BrndFeedType.php` + `src/Feed/SourceType/Brnd/` |
| Hydra/REST poster-event source | `src/Feed/EventDatabaseApiV2FeedType.php` + `src/Feed/EventDatabaseApiV2Helper.php` |

**Removed in 3.0.0:** `SparkleIOFeedType`, `EventDatabaseApiFeedType` (use `EventDatabaseApiV2FeedType`),
and `KobaFeedType` were deprecated in 2.x and removed in 3.0.0 — do not reintroduce them.

## 7. Testing

Add `tests/Feed/MyFeedTypeTest.php`. Pure unit tests (no kernel) are fine for transformation logic — see `tests/Feed/CalendarApiFeedTypeTest.php`.

**Update `tests/Service/FeedServiceTest.php`** — add `$this->assertTrue(in_array(MyFeedType::class, $feedTypes));` to `testGetFeedTypes()`. This catches the regression where the service tag stops picking up your class.

`testGetDataErrorIsNotCachedWithNormalTtl` in the same file documents the error-caching contract — read it before tweaking exception handling.

Run:

```shell
task test:api -- tests/Feed tests/Service/FeedServiceTest.php
task code-analysis
```

## 8. Smoke-test end-to-end

1. Boot the stack, log into admin.
2. Create a **FeedSource** — pick `App\Feed\MyFeedType`, fill secrets. Validation errors come from `getSchema()`.
3. Create a **Feed** on that source — the form is `getAdminFormOptions()`. Dynamic dropdowns confirm `getConfigOptions()`.
4. `GET /v2/feeds/{id}/data` (operation `_api_Feed_get_data` in `config/api_platform/feed.yaml`) — should return your normalized payload.
5. Break upstream (revoke token / dead URL) → re-request → expect `[]` with 30s cache, exception in logs.
6. Attach Feed to a slide with a matching-model template → confirm render.

## 9. Before opening the PR

- [ ] `task test:api` + `task coding-standards:php:check` clean.
- [ ] `task code-analysis` clean (PHPStan).
- [ ] `CHANGELOG.md` entry under unreleased.
- [ ] `docs/feed/my-feed-type.md` covering: required env vars, required secrets, required per-feed config keys, sample data shape (so frontend can mock).
- [ ] Class listed in `FeedServiceTest::testGetFeedTypes()`.
- [ ] No secret values logged — grep your own `$this->logger->error` calls.

## 10. Common pitfalls

- **Catching exceptions in `getData()` and returning `[]`.** Caches empty at feed-level TTL (often hours). Rethrow. Let `FeedService` handle the short error-cache.
- **Forgetting to add the class to `FeedServiceTest::testGetFeedTypes()`.** No CI failure today; quiet regression when the service tag breaks tomorrow.
- **Missing env var in `.env.test`.** Test kernel can't boot. `scripts/check-env-coverage.sh` (PostToolUse hook on `.env*`) catches the production-side miss; the test-env miss only surfaces when tests run.
- **Using `$feedsCache` for intermediate lookups.** Key collision with `FeedService`. Make your own cache pool.
- **Mutating `$feed->getConfiguration()` in `getData()` expecting persistence.** It's read-only. Documented exception: `EventDatabaseApiV2FeedType` unpublishes the owning slide via Doctrine when upstream returns 404.
- **Adding a new output model without updating frontend templates.** Templates filter `FeedSource` by `supportedFeedOutputType` — the new model is invisible until at least one template consumes it.
- **Logging secrets.** Tempting while debugging; strip before merging.

## Related

- README "Feeds" — architectural overview.
- ADR 005 — technology stack (FeedType pattern context).
- `src/Feed/FeedTypeInterface.php` — authoritative method signatures.
- `src/Feed/FeedOutputModels.php` — output model constants + shape docblocks.
- Subagent `feed-type-reviewer` — contract checks against the cookbook rules.
