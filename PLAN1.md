# Implementation Plan: OS2Display Black Screen & Auth Recovery Fixes

**Target branch:** `release/3.0.0`
**Methodology:** TDD — each step writes a failing test first, then the minimal fix
**Execution:** Each step is one PR, optimized for `claude code /loop`

## Docker compose services

All commands run inside the project's docker compose containers:

| Service | Container | Used for |
|---------|-----------|----------|
| `phpfpm` | `itkdev/php8.3-fpm` | PHP: composer, phpunit, psalm, php-cs-fixer |
| `node` | `node:24` | npm, vitest |
| `playwright` | `mcr.microsoft.com/playwright` | E2E tests |
| `mariadb` | `itkdev/mariadb` | Test database |
| `redis` | `redis:6` | Cache, sessions |

## Command reference

```bash
# PHP — tests
docker compose run --rm phpfpm composer test

# PHP — static analysis
docker compose run --rm phpfpm composer code-analysis

# PHP — coding standards check
docker compose run --rm phpfpm composer coding-standards-check

# PHP — auto-fix coding standards
docker compose run --rm phpfpm composer coding-standards-apply

# PHP — test setup (DB reset, migrations)
docker compose run --rm phpfpm composer test-setup

# PHP — clear cache
docker compose run --rm phpfpm bin/console cache:clear

# JS — install dependencies
docker compose run --rm node npm install

# JS — unit tests (after Step 0)
docker compose run --rm node npx vitest run

# JS — E2E tests
docker compose run --rm playwright npx playwright test

# JS — coding standards (if configured)
docker compose run --rm node npx prettier --check assets/
```

## Verification checklist (run before every PR)

```bash
# Build (after Step 15 adds the SW build plugin)
docker compose run --rm node npm run build

# PHP steps
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer test

# JS steps (after Step 0)
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

---

## Phase 0: Test infrastructure

### Step 0 — Add Vitest for client unit tests

**Branch:** `fix/add-client-unit-test-framework`

**Motivation:** The client codebase has no unit test framework. The
only automated coverage is Playwright E2E, which runs against a full
browser and requires the entire stack (backend, database, Redis, the
React app) to be up. E2E tests are valuable but slow, brittle under
timing variance, and incapable of isolating the kind of state-machine
bugs we're about to fix.

Consider the specific bugs in this plan:

- `reauthenticateHandler` not resetting `displayFallback` (Step 6) — a
  bug in a state transition triggered by a 401 response. To hit it
  with E2E, we'd need to set up a screen, fake a 401, and inspect the
  DOM at the exact moment the handler runs. To hit it in a unit test,
  we mount `<App>` in jsdom and dispatch a `reauthenticate` event.
- `checkLogin` dead-end on unknown status (Step 7) — requires injecting
  a malformed backend response. Trivial in a unit test, practically
  impossible in E2E without backend mocking.
- Crash-loop guard (Step 11c) — a state machine around localStorage
  with time-sensitive windows. Unit tests with fake timers cover it in
  seconds; E2E would need ~4 minutes of real wall-clock time to exercise.

Vitest integrates directly with the existing Vite config, uses the same
JSX transform, and is zero-config. The `fake-indexeddb` polyfill
(installed in this step) lets us test Step 14's IndexedDB auth-bridge
without a real browser. Every subsequent TDD step depends on this
infrastructure.

This step also establishes the convention: tests live next to the code
as `__tests__/*.test.{js,jsx}`, imports match production imports,
`jsdom` provides the DOM environment. Consistent structure makes
`/loop` runs predictable.

**Tasks:**

1. Install vitest + testing libraries:
   ```bash
   docker compose run --rm node npm install -D vitest @testing-library/react @testing-library/jest-dom jsdom fake-indexeddb
   ```

2. Add vitest config to `vite.config.js`:
   ```js
   test: {
     environment: 'jsdom',
     include: ['assets/**/*.test.{js,jsx}'],
     setupFiles: ['./assets/tests/setup.js'],
   }
   ```

3. Create `assets/tests/setup.js`:
   ```js
   import '@testing-library/jest-dom';
   ```

4. Add npm script to `package.json`:
   ```json
   "test:unit": "vitest run",
   "test:unit:watch": "vitest"
   ```

5. Write a smoke test `assets/client/util/__tests__/id-from-path.test.js`
   that tests the existing `idFromPath` utility to prove the framework works.

6. Verify:
   ```bash
   docker compose run --rm node npx vitest run
   ```

**Changelog:** Added Vitest for client-side unit testing.

---

## Phase 1: Backend bug fixes

### Step 1 — Fix `ScreenUserRequestSubscriber` entityManager->clear()

**Branch:** `fix/screen-subscriber-entity-clear`

**Bug:** When `TRACK_SCREEN_INFO=true`, this subscriber updates the
screen's `lastSeen` timestamp and client metadata on every API request,
then calls `$this->entityManager->flush()` followed by
`$this->entityManager->clear()`.

`EntityManager::clear()` with no argument detaches ALL entities from
Doctrine's identity map — not just the one that was flushed. This means
any other managed entity loaded earlier in the request lifecycle,
including the authenticated ScreenUser, its associated Screen, and the
Tenant, becomes detached.

The subscriber runs on `kernel.request` after authentication but before
the controller. When the controller later accesses `$user->getScreen()`
or TenantExtension runs its query filter, Doctrine can no longer
lazy-load from these detached entities. The request either 500s
(`EntityNotFoundException`) or returns incorrect data (tenant filter
silently inactive). Under concurrent load across a shared PHP-FPM
worker, the corruption cascades to adjacent requests.

Only triggers when `TRACK_SCREEN_INFO=true` (defaults to false). In
deployments that have opted in to screen tracking, every screen's 90-second
poll cycle runs through this subscriber, making the failure rate
proportional to screen count.

**RED — write test:**

File: `tests/EventSubscriber/ScreenUserRequestSubscriberTest.php`

Test that after `ScreenUserRequestSubscriber::onKernelRequest` runs for a
screen endpoint, the authenticated ScreenUser entity is still managed by
the entity manager (not detached).  Simulate a request to
`/v2/screens/{ULID}` with `trackScreenInfo=true`, then assert:
- `$entityManager->contains($screenUser)` is true after the subscriber runs
- The screen's tenant is still accessible via the user security token

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenUserRequestSubscriberTest
```

**GREEN — fix:**

In `src/EventSubscriber/ScreenUserRequestSubscriber.php`, replace:
```php
$this->entityManager->flush();
$this->entityManager->clear();
```
With:
```php
$this->entityManager->flush();
$this->entityManager->detach($screenUser);
```

Only detach the specific entity that was modified, not the entire identity map.

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: `entityManager->clear()` in ScreenUserRequestSubscriber no longer detaches authenticated user entities.

---

### Step 2 — Fix `getStatus()` one-shot cache delete

**Branch:** `fix/screen-auth-cache-grace-period`

**Bug:** `ScreenAuthenticator::getStatus()` is the polling endpoint the
client hits to check whether an admin has claimed its bind key. When the
admin binds the screen, a JWT + refresh token pair is written to the
cache against the session's bind key. The next time `getStatus()` is
called, it retrieves the tokens AND immediately calls
`$this->authScreenCache->deleteItem($cacheKey)` before returning them to
the client.

This treats the cache as a one-shot retrieval. If anything between the
cache read and the client's successful token storage fails — network
blip, proxy timeout, slow PHP-FPM response, browser retry, JS parse
error, kernel panic on a cheap kiosk box — the client polls again but
the cache is already empty. The response reverts to `awaitingBindKey`,
even though the screen IS bound on the backend.

From the admin's perspective: they click bind, the bind key entry UI
confirms success, but the screen keeps showing the bind key. The admin
concludes the bind failed and re-binds. Each additional bind attempt
creates new refresh tokens (Step 4 issue), each adds a ScreenUser
conflict (Step 3 issue). A cascade of downstream problems can trace
back to this single one-shot retrieval.

The fix replaces the immediate delete with a 60-second grace period.
The client has time to retry; stale tokens still clean up on their own.

**RED — write test:**

File: `tests/Security/ScreenAuthenticatorTest.php`

Test that after `getStatus()` returns a `ready` response with a token, a
second call to `getStatus()` within 60 seconds (same session) still returns
`ready` with the same token (not `awaitingBindKey`).

Currently fails because `deleteItem()` destroys the entry on first read.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenAuthenticatorTest
```

**GREEN — fix:**

In `src/Security/ScreenAuthenticator.php` `getStatus()`, replace:
```php
$this->authScreenCache->deleteItem($cacheKey);
```
With:
```php
$cacheItem->expiresAfter(60);
$this->authScreenCache->save($cacheItem);
```

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: Screen auth token cache entry now has a 60-second grace period instead of immediate deletion.

---

### Step 3 — Fix `bindScreen` for already-bound screens

**Branch:** `fix/screen-rebind-already-bound`

**Bug:** `ScreenAuthenticator::bindScreen()` is called when an admin
enters a bind key in the UI. It resolves the Screen and checks whether a
ScreenUser already exists. If yes, it throws an exception with the
message "Screen already bound." `AuthScreenBindController` catches every
exception as "Key not accepted" and returns that to the admin.

This is the exact failure mode blocking every client-side auth recovery
scenario. Consider the path: a screen loses its tokens (localStorage
cleared by browser memory pressure, IndexedDB corrupted, explicit
`reauthenticate` wiping credentials, hardware-induced browser crash
clearing storage). The screen boots, has no tokens, requests a new bind
key, and displays it. The admin sees the screen showing a key, enters
it, clicks bind. Backend: ScreenUser already exists → "Screen already
bound" → "Key not accepted."

The admin has no indication that unbinding is required first, and even
if they knew, unbinding can lose configuration depending on other
cleanup paths. The documented workaround is "delete the Screen and
create a new one" — losing history, schedules, and playlist assignments.

On the field: every screen that loses client-side auth becomes a support
ticket. The fix accepts rebind requests for already-bound screens: the
old ScreenUser and its refresh tokens are cleaned up (same cleanup as
Step 4's unbind fix), a new ScreenUser is created, the screen transparently
rebinds. Admins see the same success flow as first-time binding.

**RED — write test:**

File: `tests/Security/ScreenAuthenticatorRebindTest.php`

Test the full flow: bind a screen, then simulate the screen losing auth
and requesting a new bind key.  Call `bindScreen` again with the same
screen.  Assert that:
- The call succeeds (does not throw)
- The old ScreenUser is removed
- A new ScreenUser is created with a new JWT and refresh token
- The old refresh token is deleted from the `refresh_tokens` table

Currently fails because `bindScreen` throws "Screen already bound".

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenAuthenticatorRebindTest
```

**GREEN — fix:**

In `src/Security/ScreenAuthenticator.php` `bindScreen()`, replace:
```php
if ($screenUser) {
    throw new \Exception('Screen already bound');
}
```
With:
```php
if ($screenUser) {
    $this->cleanupScreenUser($screenUser);
}
```

Add private method:
```php
private function cleanupScreenUser(ScreenUser $screenUser): void
{
    $refreshTokens = $this->entityManager
        ->getRepository(RefreshToken::class)
        ->findBy(['username' => $screenUser->getUserIdentifier()]);

    foreach ($refreshTokens as $token) {
        $this->entityManager->remove($token);
    }

    $this->entityManager->remove($screenUser);
    $this->entityManager->flush();
}
```

Also update `src/Controller/Api/AuthScreenBindController.php` to return a
descriptive error on remaining failure cases instead of generic "Key not
accepted".

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: Screens that lost client-side auth can now rebind without manual unbind. Old refresh tokens are cleaned up.

---

### Step 4 — Fix `unbindScreen` refresh token cleanup

**Branch:** `fix/unbind-cleanup-refresh-tokens`

**Bug:** `ScreenAuthenticator::unbindScreen()` removes the ScreenUser
entity and flushes. The associated rows in the `refresh_tokens` table —
created by the Gesdinet JWT refresh bundle with the ScreenUser's
identifier in the `username` column — are not touched.

Every screen accumulates multiple refresh tokens over its lifetime: one
from the initial bind, one from each `/v2/authentication/token/refresh`
call (every 7.5 days by default with the 15-day JWT TTL). Over a year,
one screen generates ~50 refresh token rows. A 200-screen deployment
produces ~10,000 token rows per year.

When that screen is unbound and rebound (which Step 3 now allows to
succeed), the ScreenUser is recreated with a different identifier, so
the new screen doesn't inherit old tokens. The old tokens remain
queryable: `SELECT * FROM refresh_tokens WHERE username = '...'`
returns data for an entity that no longer exists. Over months of
churn, the `refresh_tokens` table grows without bound. Every token
refresh request scans this growing table; response time degrades.

A subtler failure mode: if token identifier collisions ever occur (e.g.,
a deployment bug reuses IDs, or a Ulid collision, or a deliberate ID
set for testing), the new ScreenUser inherits stale refresh tokens from
a previous life.

The fix wraps ScreenUser deletion in a transaction that first removes
all `refresh_tokens` rows matching the username. Unbinding leaves no
orphans.

**RED — write test:**

File: `tests/Security/ScreenAuthenticatorUnbindTest.php`

Test that after `unbindScreen()`:
- The ScreenUser is deleted
- All refresh tokens for that ScreenUser are deleted from `refresh_tokens`
- No orphaned rows remain

Currently fails because `unbindScreen` only deletes the ScreenUser.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenAuthenticatorUnbindTest
```

**GREEN — fix:**

In `src/Security/ScreenAuthenticator.php` `unbindScreen()`, add refresh
token cleanup before removing the ScreenUser.  Reuse the
`cleanupScreenUser` helper from Step 3 or inline the logic.

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: Unbinding a screen now removes associated refresh tokens.

---

### Step 5 — Harden `JwtTokenRefreshedSubscriber` TTL correction

**Branch:** `fix/refresh-token-ttl-resilience`

**Bug:** When a screen refreshes its JWT via
`/v2/authentication/token/refresh`, the Gesdinet bundle creates a NEW
refresh token with the bundle's default TTL of 2 hours. OS2Display needs
a 30-day TTL to match its operational model. The
`JwtTokenRefreshedSubscriber` listens for Gesdinet's
`RefreshTokenCreated` event, retrieves the freshly-created token via
`$refreshTokenManager->get($tokenString)`, modifies the `valid` field,
and persists it via `$refreshTokenManager->save()`.

Any exception in this flow silently breaks the refresh token's TTL:

- `get()` returns null because the token hasn't been committed to the
  DB yet (transaction race with the subscriber's read)
- DB connection exception during `get()` or `save()` under load
- Schema mismatch if the `valid` field migration hasn't run in the
  current deployment (known deployment hazard when running out of sequence)
- Datetime field edge cases on `valid`

When any of these triggers, the exception bubbles up — but the HTTP
response to the client is still 200 OK, because the NEW access token
was generated before the subscriber ran. The client happily stores the
new tokens and continues.

Two hours later, the refresh token silently expires. The next refresh
attempt fails with 401 "Invalid refresh token." The access token
(15-day TTL) is still valid but cannot be renewed. The client enters
the `reauthenticateHandler` flow (Step 6's bug), which wipes tokens
and dead-ends.

From the operator perspective: "the screen worked yesterday, this morning
it's black." The log shows a 401 from two hours before it went black.
No obvious connection to the earlier successful refresh.

The fix wraps the TTL upgrade in try/catch. Failure logs a critical
warning (for alerting) but allows the refresh response to succeed. A
2-hour refresh token is degraded but not broken — the next refresh
cycle gets another chance to correct the TTL. Ops has visibility; the
screen keeps running.

**RED — write test:**

File: `tests/Security/EventSubscriber/JwtTokenRefreshedSubscriberTest.php`

Unit test the subscriber directly:
1. Test that when `refreshTokenManager->get()` returns null, the subscriber
   does NOT throw — it logs a warning and the response still contains the
   token.
2. Test that when `refreshTokenManager->save()` throws a DB error, the
   subscriber catches it and the response still succeeds.

Currently fails because both cases throw uncaught exceptions.

```bash
docker compose run --rm phpfpm composer test -- --filter=JwtTokenRefreshedSubscriberTest
```

**GREEN — fix:**

In `src/Security/EventSubscriber/JwtTokenRefreshedSubscriber.php`, wrap
the TTL correction in a try/catch.  On failure, log a critical warning but
allow the refresh response to succeed with the gesdinet-default TTL:

```php
try {
    $refreshToken = $this->refreshTokenManager->get($refreshTokenString);
    if (null === $refreshToken) {
        return;
    }
    // ... existing TTL correction ...
    $this->refreshTokenManager->save($refreshToken);
} catch (\Throwable $e) {
    // Log critical — the refresh token has the wrong TTL.
    // But the JWT refresh itself succeeded, so don't break it.
}
```

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: Refresh token TTL correction failure no longer crashes the token refresh endpoint.

---

### Step 5b — Read tenant from JWT payload instead of DB in TenantScopedAuthenticator

**Branch:** `fix/authenticator-tenant-from-jwt`

**Bug:** `TenantScopedAuthenticator::doAuthenticate()` runs on every
authenticated request. For ScreenUser it calls `$user->getTenants()`,
which returns `new ArrayCollection([$this->getScreen()->getTenant()])`.
Both `getScreen()` and `getTenant()` are lazy-loaded Doctrine proxies;
this single method call triggers two SELECT queries (one against
`screen`, one against `tenant`). The authenticator then iterates the
collection, calls `$user->setActiveTenant()` (which internally calls
`getScreen()->getTenant()` again for validation), and the request
proceeds.

Downstream, `TenantExtension` — a Doctrine query filter — calls
`$user->getActiveTenant()->getId()` on every query to scope results to
the tenant. ScreenUser's `getActiveTenant()` also chains through
`getScreen()->getTenant()`. For a typical API call that loads 5 entities
with tenant filtering, the request makes 3–5 extra queries just for
tenant resolution, all redundant.

Under burst load this becomes a connection-pool problem. Common bursts:

- Mass reboot: 200 screens power-cycle simultaneously (power event,
  maintenance window, timed reboot script)
- Morning startup: school or office where screens come online within a
  10-minute window
- Network recovery: screens that were offline all come back at once

200 screens × 3 queries × 2 seconds to retry on failure = connection
pool saturation. MariaDB's default `max_connections` is 100–200.
Connection waits exceed PHP's 30-second request timeout, requests 500,
clients retry, the cascade amplifies. Screens appear to "fail to boot"
but are actually hitting auth timeouts.

The JWT payload already contains `tenantKey`, `title`, `description`,
and `roles` per tenant (set by `JWTCreatedListener`). Adding `tenantId`
means the full tenant identity is in the signed token. The authenticator
can validate the tenant key against the JWT claims (no DB), then use
Doctrine's `getReference(Tenant::class, $ulid)` — which returns a proxy
with the ID set but **never initializes it**. Downstream code calls
`$tenant->getId()->toBinary()`, which works directly on the uninitialized
proxy because Doctrine stores the identifier on the proxy object itself.

Result: zero DB queries during screen authentication. The bottleneck
moves to the actual data queries the kiosk is making, which is the
actual intended workload.

**RED — write tests:**

File: `tests/Security/TenantScopedAuthenticatorJwtTest.php`

1. Bind a screen, get a JWT.  Make an API request with the JWT.  Assert
   200.  Use Doctrine SQL logger to assert **no query touches the
   `screen` table** during the request.
2. Assert that the `tenant` table is also NOT queried (only
   `getReference` is used).
3. JWT with a `tenantKey` not matching the `Authorization-Tenant-Key`
   header returns 401.
4. JWT without a `tenants` claim falls back to the DB path (backward
   compatibility with tokens issued before this change).
5. JWT with `tenants` that include `tenantId` — verify `getReference`
   is used and tenant `getId()` returns the correct ULID.

File: `tests/Security/EventListener/JWTCreatedListenerTest.php`

1. Create a JWT for a ScreenUser.  Decode the payload.  Assert each
   tenant object contains `tenantKey`, `title`, `description`, `roles`,
   AND `tenantId`.
2. Create a JWT for a regular User.  Assert `tenantId` is present in
   each tenant entry.

```bash
docker compose run --rm phpfpm composer test -- --filter=TenantScopedAuthenticatorJwtTest
docker compose run --rm phpfpm composer test -- --filter=JWTCreatedListenerTest
```

**GREEN — implement:**

**Step A — Add `tenantId` to JWT payload:**

In `src/Entity/ScreenUser.php` `getUserRoleTenants()`, add the ID:

```php
public function getUserRoleTenants(): Collection
{
    $tenant = $this->getScreen()->getTenant();

    $userRoleTenant = new \stdClass();
    $userRoleTenant->tenantKey = $tenant->getTenantKey();
    $userRoleTenant->tenantId = $tenant->getId()?->jsonSerialize();
    $userRoleTenant->title = $tenant->getTitle();
    $userRoleTenant->description = $tenant->getDescription();
    $userRoleTenant->roles = $this->getRoles();

    return new ArrayCollection([$userRoleTenant]);
}
```

In `src/Entity/UserRoleTenant.php` `jsonSerialize()`, add the ID:

```php
public function jsonSerialize(): array
{
    return [
        'tenantKey' => $this->getTenant()?->getTenantKey(),
        'tenantId' => $this->getTenant()?->getId()?->jsonSerialize(),
        'title' => $this->getTenant()?->getTitle(),
        'description' => $this->getTenant()?->getDescription(),
        'roles' => $this->getRoles(),
    ];
}
```

Now every JWT contains both `tenantKey` and `tenantId` per tenant.

**Step B — Add `preloadTenant` to ScreenUser:**

In `src/Entity/ScreenUser.php`:

```php
private ?Tenant $preloadedTenant = null;

public function preloadTenant(Tenant $tenant): void
{
    $this->preloadedTenant = $tenant;
}

public function getActiveTenant(): Tenant
{
    return $this->preloadedTenant ?? $this->getScreen()->getTenant();
}

public function setActiveTenant(Tenant $activeTenant): self
{
    if ($this->preloadedTenant !== null) {
        // Tenant was resolved from JWT — skip DB validation.
        return $this;
    }

    if ($this->getScreen()->getTenant() !== $activeTenant) {
        throw new \InvalidArgumentException(
            'Active Tenant cannot be set. User does not have access to Tenant: '
            .$activeTenant->getTenantKey()
        );
    }

    return $this;
}

public function getTenants(): Collection
{
    if ($this->preloadedTenant !== null) {
        return new ArrayCollection([$this->preloadedTenant]);
    }

    return new ArrayCollection([$this->getScreen()->getTenant()]);
}
```

**Step C — Update TenantScopedAuthenticator:**

Update service definition in `config/services.yaml`:

```yaml
app.tenant_scoped_authenticator:
    class: App\Security\TenantScopedAuthenticator
    parent: lexik_jwt_authentication.security.jwt_authenticator
    calls:
        - [setTenantDependencies, ['@doctrine.orm.entity_manager', '@lexik_jwt_authentication.jwt_manager']]
```

In `src/Security/TenantScopedAuthenticator.php`:

```php
use App\Entity\ScreenUser;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Uid\Ulid;

class TenantScopedAuthenticator extends JWTAuthenticator
{
    final public const string AUTH_TENANT_ID_HEADER = 'Authorization-Tenant-Key';

    private ?EntityManagerInterface $entityManager = null;
    private ?JWTTokenManagerInterface $jwtManager = null;

    public function setTenantDependencies(
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
    ): void {
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
    }

    final public function doAuthenticate(Request $request): Passport
    {
        $passport = parent::doAuthenticate($request);
        $user = $passport->getUser();

        if (!$user instanceof TenantScopedUserInterface) {
            throw new AuthenticationException('User class is not tenant scoped');
        }

        // Fast path for ScreenUser: validate tenant from JWT, use
        // getReference() for the entity.  Zero DB queries.
        if ($user instanceof ScreenUser) {
            $resolved = $this->resolveScreenTenantFromJwt($request, $user);
            if ($resolved) {
                return $passport;
            }
        }

        // Fallback: original DB path for regular Users and legacy JWTs
        // that predate the tenantId claim.
        if ($user->getTenants()->isEmpty()) {
            throw new AuthenticationException('User has no tenant access');
        }

        if ($request->headers->has(self::AUTH_TENANT_ID_HEADER)) {
            $requestTenantKey = $request->headers->get(self::AUTH_TENANT_ID_HEADER);
            $hasTenantAccess = false;

            foreach ($user->getTenants() as $tenant) {
                if ($tenant->getTenantKey() === $requestTenantKey) {
                    $user->setActiveTenant($tenant);
                    $hasTenantAccess = true;
                    break;
                }
            }

            if (!$hasTenantAccess) {
                throw new AuthenticationException(
                    'Unknown tenant key or user has no access to tenant: '.$requestTenantKey
                );
            }
        } else {
            $defaultTenant = $user->getTenants()->first();
            $user->setActiveTenant($defaultTenant);
        }

        return $passport;
    }

    private function resolveScreenTenantFromJwt(Request $request, ScreenUser $user): bool
    {
        if (null === $this->jwtManager || null === $this->entityManager) {
            return false;
        }

        $authHeader = $request->headers->get('Authorization', '');
        $rawToken = str_replace('Bearer ', '', $authHeader);

        if ('' === $rawToken) {
            return false;
        }

        try {
            $payload = $this->jwtManager->parse($rawToken);
        } catch (\Throwable) {
            return false;
        }

        $jwtTenants = $payload['tenants'] ?? [];
        if (empty($jwtTenants)) {
            return false;
        }

        // Find the matching tenant from JWT claims.
        $requestTenantKey = $request->headers->get(self::AUTH_TENANT_ID_HEADER);
        $matchedTenant = null;

        foreach ($jwtTenants as $jwtTenant) {
            $key = is_object($jwtTenant)
                ? ($jwtTenant->tenantKey ?? null)
                : ($jwtTenant['tenantKey'] ?? null);
            $id = is_object($jwtTenant)
                ? ($jwtTenant->tenantId ?? null)
                : ($jwtTenant['tenantId'] ?? null);

            if (null === $key || null === $id) {
                // Legacy JWT without tenantId — fall back to DB path.
                return false;
            }

            if (null === $requestTenantKey || $key === $requestTenantKey) {
                $matchedTenant = ['key' => $key, 'id' => $id];
                break;
            }
        }

        if (null === $matchedTenant) {
            throw new AuthenticationException(
                'Unknown tenant key or user has no access to tenant: '.$requestTenantKey
            );
        }

        // getReference() returns a Doctrine proxy with the ID set but
        // NEVER queries the database.  Downstream code that only calls
        // $tenant->getId() (like TenantExtension) works without any
        // proxy initialization.
        $tenantUlid = Ulid::fromString($matchedTenant['id']);
        $tenantRef = $this->entityManager->getReference(Tenant::class, $tenantUlid);

        $user->preloadTenant($tenantRef);

        return true;
    }
}
```

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: Screen authentication validates tenant entirely from the JWT payload using Doctrine `getReference()`. Zero DB queries during authentication. Legacy JWTs without tenant IDs fall back to the original DB path.

---

## Phase 2: Frontend bug fixes

### Step 6 — Fix `displayFallback` not reset in reauthenticateHandler

**Branch:** `fix/display-fallback-reset-on-reauth`

**Bug:** `App.jsx` has three mutually exclusive render conditions, in
priority order:

1. `displayFallback === true` → render the fallback image (OS2Display logo)
2. `screen != null` (after displayFallback is false) → render the screen content
3. Otherwise → render nothing

The `<body>` background is black by default in the client CSS. When all
three conditions miss, the DOM has no visible children and the user
sees a pure black screen — the exact failure mode operators report most
often.

`reauthenticateHandler` fires when the data-sync layer receives a 401.
It calls `tokenService.stopRefreshing()`, `appStorage.clearAuth()` (wipes
localStorage tokens), `setScreen(null)` (clears current content), and
`checkLogin()` (restarts the login polling loop). Nowhere does it
set `displayFallback` back to `true`.

At the moment reauth begins: `displayFallback` is `false` (set earlier
when content loaded successfully) AND `screen` is `null` (just cleared).
Both conditions miss. Black screen. If `checkLogin` eventually resolves
and new content loads, the state self-corrects. But if login fails —
refresh token expired (Step 5's TTL bug makes this common), backend
briefly unavailable, network issue — the screen stays black indefinitely
with no visible indication that recovery is in progress.

This bug is the proximate cause of most "screen went black and never
came back" support tickets. The hardware is working, the browser is
running, but the render tree is empty. The fix adds one line —
`setDisplayFallback(true)` at the start of the handler — so users see
the known-good fallback image during reauth, not an ambiguous black
screen.

**RED — write test:**

File: `assets/client/components/__tests__/app-reauth.test.jsx`

Mount `<App>` in test, simulate:
1. Set `displayFallback` to false (via dispatching `contentNotEmpty`)
2. Trigger `reauthenticate` event
3. Mock `tokenService.refreshToken` to reject
4. Assert that the fallback div is rendered after reauth failure

Currently fails — fallback div is absent after reauth failure.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/app.jsx` `reauthenticateHandler` catch block, add
before `checkLogin()`:
```js
setDisplayFallback(true);
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Fixed: Fallback image now shows during auth recovery instead of black screen.

---

### Step 6a — Fix `checkToken` clear-error typo

**Branch:** `fix/check-token-clear-typo`

**Bug:** `tokenService.checkToken()` sets error codes based on the JWT's
expire state, then tries to clear them when the token transitions back
to valid:

```js
} else {
    const err = statusService.error;
    if (
        err !== null &&
        [
            constants.TOKEN_EXPIRED,
            constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
        ].includes(err)
    ) {
        statusService.setError(null);
    }
}
```

`statusService.error` contains the error CODES: `"ER105"` or `"ER106"`.
But the `includes` check compares against the expire STATE names:
`constants.TOKEN_EXPIRED = "Expired"` and
`constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED = "ValidShouldHaveBeenRefreshed"`.

The comparison is `["ER105", "ER106"]` vs `["Expired",
"ValidShouldHaveBeenRefreshed"]`. These never match. The `setError(null)`
line never executes. Once `checkToken` sets ER105 or ER106, those codes
stay in the URL until something else clears them (only `checkLogin`
during a rebind does this reliably).

Visible symptom: screens that briefly experience token lateness show
`?error=ER106` in the URL permanently, even after the next successful
refresh. Ops teams see the error code and investigate — but the screen
is actually fine and has been for hours.

This bug predates every other change in the plan and should be fixed
first because:

1. It's a one-line typo, trivial to fix
2. It affects production NOW, independent of the SW work
3. Step 18's error taxonomy audit assumes clear paths actually work —
   this needs to be true before Step 18 runs
4. `token-service.js` is deleted in Step 15, so the fix needs to land
   and be validated before Step 15 rewrites the logic in the SW

**RED — write test:**

File: `assets/client/service/__tests__/token-service-clear-error.test.js`

1. Manually set `statusService.error = constants.ERROR_TOKEN_EXPIRED`
   (ER105).
2. Set up a valid, non-expired token in `appStorage`.
3. Call `tokenService.checkToken()`.
4. Assert `statusService.error === null` after the call.

This test currently fails — the bug keeps the error set.

Add a second test for ER106 symmetry:

1. Set `statusService.error = constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED`
2. Valid fresh token in appStorage.
3. Call `checkToken()`.
4. Assert `statusService.error === null`.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/service/token-service.js` `checkToken`, change:

```diff
-            [
-                constants.TOKEN_EXPIRED,
-                constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
-            ].includes(err)
+            [
+                constants.ERROR_TOKEN_EXPIRED,
+                constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
+            ].includes(err)
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
```

**Changelog:** Fixed: Token error codes ER105 and ER106 now clear from the URL when the token state returns to valid. Previous comparison used state names instead of error codes, silently never matching.

---

### Step 7 — Fix `checkLogin` dead-end on unknown status

**Branch:** `fix/check-login-unknown-status-retry`

**Bug:** `checkLogin()` is the boot-time polling loop that asks the
backend "has an admin claimed my bind key yet?" It calls
`tokenService.checkLogin()` which returns an object with a `status`
field. The current implementation handles two cases explicitly:

- `'ready'` — admin has bound the screen; store tokens, start content
- `'awaitingBindKey'` — not yet; schedule another `checkLogin` in 5s

Any other value falls through with no action. The Promise resolves, no
retry is scheduled, the polling loop stops. The screen sits waiting for
an event that never fires.

Unknown statuses can arise from a surprising number of sources:
- Backend returning a new status value after a protocol update (version
  skew between client and API during rolling deployment)
- Malformed JSON from a misconfigured reverse proxy or cache layer
- Response body truncation from network middleware
- HTTP 200 with empty body from a Redis cache edge case
- Typo in a status string on either side

When it happens, the screen shows the bind key but never transitions to
content — even after the admin successfully binds. From the admin side,
the bind appears to fail (no visible state change on the screen). They
re-bind, which now works because Step 3 allows rebinding, but the new
bind cycle also dead-ends on the same unknown status.

The fix adds an `else` branch that retries `checkLogin` on any
unexpected status. Unknown becomes transient rather than permanent.
When the backend resolves (protocol mismatch fixed, cache flushed,
etc.), the screen self-heals on the next poll.

**RED — write test:**

File: `assets/client/components/__tests__/app-login.test.jsx`

Mock `tokenService.checkLogin` to resolve with
`{ status: 'somethingUnexpected' }`.  Assert that `restartLoginTimeout` is
called (login polling continues).

Currently fails — no action taken, no retry scheduled.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/app.jsx` `checkLogin`, add an else clause:
```js
} else {
    restartLoginTimeout();
}
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
```

**Changelog:** Fixed: Login polling no longer stops on unexpected status responses.

---

### Step 8 — Fix `screenHandler` undefined guard

**Branch:** `fix/screen-handler-undefined-guard`

**Bug:** `screenHandler` listens for the custom `screen` event
dispatched by the data-sync layer. Events are created with
`new CustomEvent('screen', { detail: { screen: screenData } })` and the
handler reads `event.detail.screen`. The existing guard:

```js
if (screenData !== null) {
    setScreen(screenData);
}
```

This catches explicit `null` but not `undefined`. When an event is
dispatched without the `screen` key (or with `detail: {}`),
`event.detail.screen` is `undefined`. `undefined !== null` evaluates to
`true`. `setScreen(undefined)` executes. The `screen` state becomes
`undefined`, which is falsy in the app's render check.

Combined with `displayFallback = false` (from an earlier successful
render), both render conditions fail — we're back at the Step 6 black
screen, from a different entry point.

Dispatch sites that omit the screen key include certain error paths in
the data-sync layer where a screen-switch cycle begins but the new
screen data hasn't finished loading. The old screen is cleared, the
new screen event fires without data, then the fetch completes and fires
another event with the real data — but between those two events, the
screen is black.

The fix changes `!==` to `!=`. The loose comparison catches both `null`
and `undefined` because `null == undefined` is true by spec. This is
one of the few cases where `!=` is preferred over `!==`: it's
specifically designed as a nullish check. The linter should be
configured to allow it here.

**RED — write test:**

File: `assets/client/components/__tests__/app-screen-handler.test.jsx`

Dispatch a `screen` event with `detail: {}` (no `screen` property).
Assert that `screen` state is NOT set to `undefined`.

Currently fails — `undefined !== null` passes the guard and `setScreen(undefined)` is called.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/app.jsx` `screenHandler`, change:
```js
if (screenData !== null) {
```
To:
```js
if (screenData != null) {
```

This catches both `null` and `undefined`.

**Verify:**
```bash
docker compose run --rm node npx vitest run
```

**Changelog:** Fixed: Screen handler no longer accepts undefined screen data.

---

### Step 9 — Fix `PullStrategy.start()` missing error handling

**Branch:** `fix/pull-strategy-start-catch`

**Bug:** `PullStrategy.start()` is the entry point for the client's
content polling loop. It fetches the screen's data, then registers a
`setInterval` to re-fetch every 90 seconds:

```js
start() {
    this.getScreen(this.entryPoint).then(() => {
        this.activeInterval = setInterval(
            () => this.getScreen(this.entryPoint),
            this.interval,
        );
    });
}
```

The interval registration lives inside `.then()`. If the initial
`getScreen` rejects — for any reason, including transient ones —
`.then()` never fires. The interval is never created. The promise
rejection bubbles up as an unhandled rejection.

From that moment, the screen has no content polling. No amount of time
passing, no backend recovery, no network restoration will trigger a
retry. `start()` was called once at boot and will not be called again
without a full page reload.

The most common trigger is boot-time timing collisions: the screen
boots while the backend is warming up after a deployment, the initial
fetch times out, the polling loop never starts. Another common cause is
a screen booting during a brief network outage — content loads fine
moments later, but the client doesn't know to try again.

The fix uses `.catch()` to log the error and `.finally()` to
unconditionally register the interval. Initial fetch failure is
survivable: the interval still runs, and the next tick (90 seconds
later) retries. A screen that boots during a backend restart recovers
within two minutes with no manual intervention.

**RED — write test:**

File: `assets/client/data-sync/__tests__/pull-strategy.test.js`

Create a PullStrategy with a mock `apiHelper` that throws on `getPath`.
Call `start()`.  Assert that:
- The error is caught (no unhandled rejection)
- The periodic interval is created (recovery continues)

Currently fails — `.then()` never fires, interval never starts.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/data-sync/pull-strategy.js` `start()`:
```js
start() {
    this.getScreen(this.entryPoint)
      .catch((err) => {
        logger.error("Initial getScreen failed:", err);
      })
      .finally(() => {
        this.stop();
        this.activeInterval = setInterval(
          () => this.getScreen(this.entryPoint),
          this.interval,
        );
      });
}
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
```

**Changelog:** Fixed: Pull strategy interval now starts even if the initial fetch fails.

---

### Step 10 — Fix `findNextSlide` with empty array

**Branch:** `fix/find-next-slide-empty-array`

**Bug:** `region.jsx` advances through its slides using modular
arithmetic. When a slide completes, `slideDone` computes the next
index:

```js
const nextIndex = (currentSlideIndex + 1) % slides.length;
const nextSlide = slides[nextIndex];
setCurrentSlide(nextSlide);
```

This works as long as `slides.length > 0`. With an empty array,
JavaScript evaluates `0 % 0` as `NaN`. Array indexing with `NaN`
returns `undefined`. `setCurrentSlide(undefined)` runs. The Slide
component receives undefined props and throws on the next render
(typically "Cannot read properties of undefined (reading 'type')" or
similar, depending on the slide template).

The region-level ErrorBoundary catches the throw. Before Step 11b,
the region stays in the error state forever — one dead region on an
otherwise working screen. After Step 11b, the region auto-reloads
after 30 seconds, but that's treating the symptom.

Empty slide arrays can occur in legitimate scenarios:

- Schedule gap: a region's playlist has slides scheduled for specific
  time windows; between windows, the effective slide list is empty
- All scheduled slides have already passed (admin configuration error,
  or NTP drift on the screen pushing "now" past the schedule end)
- Backend returns empty slides during a transitional state

The fix adds an early return in `slideDone` when `slides` is empty or
undefined. Log a warning (so ops can investigate). The region keeps
its current state (last slide, or blank) until new slides arrive from
the next data-sync cycle. No ErrorBoundary trigger, no auto-reload,
no visible error to the end user.

**RED — write test:**

File: `assets/client/components/__tests__/region-empty-slides.test.jsx`

Render a `<Region>` with an initial slide, then dispatch
`regionContent-{id}` with an empty slides array.  After the current slide
calls `slideDone`, assert that no exception is thrown.

Currently produces NaN from `0 % 0`.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/components/region.jsx` `slideDone`, add an early return:
```js
const slideDone = (slide) => {
    if (!slides || slides.length === 0) {
        logger.warn("slideDone called with empty slides array");
        return;
    }
    // ... rest of existing logic
};
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
```

**Changelog:** Fixed: Slide progression no longer crashes when the slide array is empty.

---

### Step 11 — Add top-level ErrorBoundary

**Branch:** `fix/top-level-error-boundary`

**Bug:** `index.jsx` renders `<App />` directly with no error boundary
above it. The existing ErrorBoundary component is applied inside App
at the region and slide levels, but nothing catches errors in App
itself.

React's error handling semantics: if a component throws during render
and no ancestor ErrorBoundary exists, React unmounts the entire
component tree. The root div becomes empty. With the `<body>` CSS
background set to black, the user sees a black screen — the same
visible symptom as the unprotected cases in Steps 6 and 8, but from a
different cause and with worse consequences: the whole React app is
gone, not just one region.

App-level crashes originate from a narrow set of places — state
initialization, context provider setup, the top-level useEffect hooks
handling auth and boot — but the blast radius when they happen is
total. A single uncaught throw in any of App's initialization paths
kills the page.

Observed triggers:

- Corrupted state in localStorage causing App's initial read to throw
- A context provider throwing during construction (upstream library
  bugs)
- Race conditions during fast screen-switch events unmounting App
  mid-lifecycle
- Third-party library errors (Sentry, logger) during App init

Without a top-level boundary, the SW watchdog (Step 15) is the only
recovery mechanism, but that requires a 30-second timeout. 30 seconds
of black screen is a long time on a public display.

The fix wraps `<App>` in an ErrorBoundary with `reloadTimeout={15000}`.
React catches the error, renders the fallback UI briefly, then reloads
the page. The hardcoded 15000 is a boot-time fallback (config isn't
loaded yet at this outer boundary — Step 11a's config applies only to
inner boundaries). Recovery happens automatically in under 15 seconds,
well before the SW watchdog would need to intervene.

**RED — write test:**

File: `assets/client/__tests__/index-error-boundary.test.jsx`

Render `<App>` wrapped in `<ErrorBoundary reloadTimeout={...}>`.
Make App throw during render.  Assert:
- The error is caught (ErrorBoundary fallback UI renders)
- After timeout, `window.location.reload` is called

Currently fails — App crash unmounts the entire React tree.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/index.jsx`, wrap `<App>` with `<ErrorBoundary>`:
```jsx
import ErrorBoundary from "./components/error-boundary.jsx";

root.render(
  <ErrorBoundary reloadTimeout={15000}>
    <App preview={preview} previewId={previewId} />
  </ErrorBoundary>
);
```

Note: the hardcoded 15000 is a boot-time fallback.  Once `App` mounts
and loads config, the actual `appErrorReloadTimeout` from the config
is used for the region and inner ErrorBoundaries.  The top-level
boundary wraps `App` itself so config isn't available yet — the fallback
is acceptable here.

Update `assets/client/components/error-boundary.jsx` to support
`reloadTimeout` prop — schedule `window.location.reload()` after
timeout, clear timer in `componentWillUnmount`.

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Top-level error boundary with auto-reload recovers from unhandled React crashes.

---

### Step 11a — Add error recovery timeout to client config

**Branch:** `feat/error-recovery-config`

**Motivation:** Steps 11, 11b, 11c, and 15 all introduce timeouts:
watchdog, ErrorBoundary reload, crash-loop window, backoff duration,
heartbeat interval, global error reload delay. Each has a sensible
default, but "sensible" depends on hardware and content.

On modern kiosk hardware with lightweight templates, a 30-second
watchdog is generous — the main thread shouldn't block for more than a
few hundred milliseconds. On a 2016-vintage ARM kiosk running a heavy
canvas-based weather template at 4K resolution, a single frame render
can exceed 5 seconds, and paint under memory pressure can stall for
15 seconds. The same 30-second watchdog that's generous on new
hardware is aggressive on old hardware, causing false watchdog kills
during legitimate slow renders.

Exposing 8 separate timeouts to operators (watchdog, heartbeat,
region-reload, app-reload, global-error-delay, crash-max, crash-window,
crash-backoff) is too much surface area. Operators don't have the
information to tune each one independently — they think in terms of
"how long before recovery kicks in." One value that everything derives
from matches that mental model:

```
CLIENT_ERROR_RECOVERY_TIMEOUT=30000   # default
        ↓
heartbeatInterval:        t / 6    (5s)
globalErrorReloadDelay:   t / 3    (10s)
appErrorReloadTimeout:    t / 2    (15s)
regionErrorReloadTimeout: t        (30s)
watchdogTimeout:          t        (30s)
crashGuardWindow:         t * 2    (60s)
crashGuardBackoff:        t * 4    (120s)
```

A heavy-kiosk operator sets `CLIENT_ERROR_RECOVERY_TIMEOUT=120000` and
every timeout scales proportionally: 20s heartbeats, 60s app reload,
120s watchdog, 240s crash window, 8-minute backoff. No false triggers
from slow renders; if something genuinely breaks, recovery still
happens within a few minutes.

The compiler pass (Step 12) hashes this env var into the
`X-OS2Display-Config-Hash` response header, so the SW detects when it
changes and re-fetches config without a deploy.

**RED — write test:**

File: `tests/Controller/Client/ClientConfigControllerTest.php`

1. GET `/config/client`.  Assert response contains `errorRecoveryTimeout`
   with default value `30000`.
2. Override env var `CLIENT_ERROR_RECOVERY_TIMEOUT=120000`, GET
   `/config/client`, assert the value is reflected.

File: `assets/client/util/__tests__/error-recovery-timeouts.test.js`

1. `deriveTimeouts(30000)` returns the expected ratios (heartbeat=5000,
   appReload=15000, watchdog=30000, crashWindow=60000, etc.).
2. `deriveTimeouts(120000)` scales everything proportionally.
3. `deriveTimeouts(undefined)` uses the default 30000.

```bash
docker compose run --rm phpfpm composer test -- --filter=ClientConfigControllerTest
docker compose run --rm node npx vitest run
```

**GREEN — implement:**

Add env var to `.env`:

```env
CLIENT_ERROR_RECOVERY_TIMEOUT=30000
```

Update `src/Controller/Client/ClientConfigController.php` — add one
constructor argument and one response key:

```php
public function __construct(
    // ... existing params ...
    private readonly int $errorRecoveryTimeout,
) {}

public function __invoke(): Response
{
    return new JsonResponse([
        // ... existing keys ...
        'errorRecoveryTimeout' => $this->errorRecoveryTimeout,
    ]);
}
```

Update `config/services.yaml` bindings:

```yaml
App\Controller\Client\ClientConfigController:
    arguments:
        # ... existing args ...
        $errorRecoveryTimeout: '%env(int:CLIENT_ERROR_RECOVERY_TIMEOUT)%'
```

Create `assets/client/util/error-recovery-timeouts.js` — the single
function subsequent steps use:

```js
/**
 * Derive all error recovery timeouts from one operator-facing value.
 *
 * Mental model: "how long before recovery kicks in" = t.
 * All downstream timeouts scale proportionally:
 *   - heartbeat 6x faster than t (granularity)
 *   - app reload at t/2 (fast for whole-app crashes)
 *   - region reload at t (slower — other regions still work)
 *   - watchdog at t (matches region)
 *   - crash window 2t, backoff 4t (loop prevention scale)
 */
export function deriveTimeouts(errorRecoveryTimeout = 30000) {
    const t = errorRecoveryTimeout;
    return {
        heartbeatInterval:        Math.round(t / 6),
        globalErrorReloadDelay:   Math.round(t / 3),
        appErrorReloadTimeout:    Math.round(t / 2),
        regionErrorReloadTimeout: t,
        watchdogTimeout:          t,
        crashGuardWindow:         t * 2,
        crashGuardBackoff:        t * 4,
        crashGuardMaxCrashes:     5,  // fixed — not time-based
    };
}
```

Update `src/DependencyInjection/Compiler/ApiResponseHeaderPass.php` to
include `CLIENT_ERROR_RECOVERY_TIMEOUT` in the config hash so the SW
detects when it changes.

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Added: `CLIENT_ERROR_RECOVERY_TIMEOUT` env var controls all error recovery timeouts. Downstream timeouts (heartbeat, watchdog, ErrorBoundary reload, crash-guard window/backoff) derived from this single value as ratios. Operators scale the whole recovery system with one number.

---

### Step 11b — Region ErrorBoundary with auto-recovery

**Branch:** `fix/region-error-boundary-reload`

**Motivation:** The existing ErrorBoundary component catches render
errors in the region tree and displays a fallback error UI. This is the
right default for a desktop app where a developer sees the error
boundary output and fixes the code. On an unattended 24/7 kiosk, a
permanent error UI is a dead region — the rest of the screen continues
running, but that portion is lost until a human intervenes.

For a customer deployment where screens show information to the
public (transit signs, wayfinding, cafeteria menus), a region
permanently stuck in "Something went wrong" is worse than useless —
it's actively misleading. Worse, region errors usually indicate
transient issues: a malformed slide payload, a CDN timeout for a
media asset, a template JS error triggered by an edge-case prop. The
next data-sync cycle (90 seconds later) usually has the correct data,
but ErrorBoundaries don't retry rendering — they stay in the error
state until something forces React to remount.

Adding a `reloadTimeout` prop to ErrorBoundary gives operators a simple
recovery primitive. Region-level boundaries get a 30-second timeout
(matches `errorRecoveryTimeout`). If a region's render fails, the error
UI shows briefly, then the page reloads. A fresh boot pulls fresh data;
if the error was transient, the screen self-heals. If the error is
persistent, it re-triggers on the next boot — and the crash-loop guard
(Step 11c) catches that pattern and stops the reload cycle.

This step preserves backward compatibility: `<ErrorBoundary>` without
`reloadTimeout` keeps the original "display the error forever" behavior.
Only region.jsx passes the timeout. Callers opting into auto-recovery
do so explicitly.

**RED — write test:**

File: `assets/client/components/__tests__/error-boundary-reload.test.jsx`

1. Render `<ErrorBoundary reloadTimeout={1000}>` around a component that
   throws.  Assert `window.location.reload` is called after ~1000ms.
2. Render `<ErrorBoundary>` (no reloadTimeout) around a throwing component.
   Assert `window.location.reload` is NOT called (existing behavior preserved).
3. Render `<ErrorBoundary reloadTimeout={1000}>`, trigger error, then unmount
   before timeout.  Assert `window.location.reload` is NOT called (timer
   cleared on unmount).

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — fix:**

In `assets/client/components/error-boundary.jsx`:

```js
componentDidCatch(error, errorInfo) {
    // ... existing logging and errorHandler call ...

    const { reloadTimeout = null } = this.props;
    if (reloadTimeout !== null) {
        this.reloadTimer = setTimeout(() => {
            window.location.reload();
        }, reloadTimeout);
    }
}

componentWillUnmount() {
    if (this.reloadTimer) {
        clearTimeout(this.reloadTimer);
    }
}
```

In `assets/client/components/region.jsx`, pass the timeout from config
(loaded in `App` and passed via context or prop, or read from the config
loader directly):

```diff
- <ErrorBoundary>
+ <ErrorBoundary reloadTimeout={config?.regionErrorReloadTimeout ?? 30000}>
```

The default of 30000 matches the `.env` default.  Operators can increase
it for deployments with heavy templates that might trigger longer renders.

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Region ErrorBoundary auto-reloads the page after 30 seconds if a region crashes, preventing permanent broken regions on kiosk screens.

---

### Step 11c — Global error handlers with crash-loop guard

**Branch:** `fix/global-error-handlers-crash-guard`

**Motivation:** React's ErrorBoundary catches errors during render,
commitPhase, and constructors. It does NOT catch:

- Errors in event handlers (onClick, onChange, etc.)
- Errors in setTimeout/setInterval callbacks
- Errors in requestAnimationFrame callbacks
- Unhandled Promise rejections
- Errors in the data-sync layer (which lives outside the React tree)
- Errors in fetch callbacks
- Errors thrown by addEventListener callbacks the app attached

In the OS2Display client, most runtime work happens in these excluded
zones: the pull-strategy runs setTimeout chains, tokenService does fetch
callbacks, the logger subscribes to event dispatches, template slides
use animation frames for transitions. An error in any of these is
completely invisible to ErrorBoundaries. It logs to console (maybe) and
the app silently degrades or hangs.

`window.onerror` and `window.addEventListener('unhandledrejection')`
catch everything the React boundaries miss. They're the last-resort
safety net. Without them, a kiosk that hits an async error just...
stops working, with no recovery path short of a SW watchdog timeout
(30+ seconds at best).

But global handlers alone aren't enough. They need a crash-loop guard.
Consider the failure mode: a persistent bug — corrupted localStorage
state, a bad backend response the client can't handle, a template that
always throws — crashes the app immediately on every boot. Without a
guard, the app starts → crashes → reloads → crashes → reloads in a
tight loop. Hundreds of reloads per minute. Battery drain on battery-
backed hardware. Backend flood from the reboot bursts. Log file
saturation. Eventually hardware thermal throttling.

The guard tracks crash count in a rolling window. N crashes within
window = crash loop detected. Suppress reloads for a backoff period
(long enough that whatever's wrong has time to recover — backend
restart finishing, network clearing, cache expiring). After backoff,
try again. If still broken, extended backoff. If recovered, the counter
resets on the next successful boot.

Using localStorage for the guard state is acceptable here because:
1. The guard runs before the SW is registered (boot-time), so IndexedDB
   async access is awkward
2. Its state is tiny (3 numbers)
3. The consequences of storage loss are benign (counter resets, guard
   lets the next crash through)

Step 15's SW-side crash guard supersedes this one — moving state to
IndexedDB where the SW can also read it, enabling cross-thread
coordination (SW watchdog reloads also count against the guard).

**RED — write test:**

File: `assets/client/util/__tests__/crash-guard.test.js`

1. Call `crashGuard.recordCrash()` once.  Assert `shouldReload()` returns true.
2. Call `recordCrash()` 5 times within 60 seconds.  Assert `shouldReload()`
   returns false (crash-loop detected — back off).
3. Call `recordCrash()` 5 times, wait for the backoff window to expire, call
   again.  Assert `shouldReload()` returns true (loop broken).
4. Call `crashGuard.reset()`.  Assert crash count is zero.

File: `assets/client/util/__tests__/global-error-handlers.test.js`

1. Dispatch a `window.onerror` event.  Assert the crash guard is consulted.
2. Dispatch an `unhandledrejection` event.  Assert the crash guard is consulted.
3. When `shouldReload()` returns true, assert `window.location.reload` is called
   after a short delay.
4. When `shouldReload()` returns false (crash loop), assert reload is NOT called.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — implement:**

Create `assets/client/util/crash-guard.js`:

```js
const STORAGE_KEY = 'os2display_crash_guard';

// Defaults match .env — overridden after config loads.
let maxCrashes = 5;
let crashWindow = 60_000;
let backoffDuration = 120_000;

function getState() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : { count: 0, firstCrash: null, backoffUntil: null };
    } catch {
        return { count: 0, firstCrash: null, backoffUntil: null };
    }
}

function saveState(state) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch {}
}

const crashGuard = {
    /** Call after config loads to override defaults. */
    configure({ crashGuardMaxCrashes, crashGuardWindow, crashGuardBackoff }) {
        if (crashGuardMaxCrashes) maxCrashes = crashGuardMaxCrashes;
        if (crashGuardWindow) crashWindow = crashGuardWindow;
        if (crashGuardBackoff) backoffDuration = crashGuardBackoff;
    },

    recordCrash() {
        const now = Date.now();
        const state = getState();

        if (!state.firstCrash || now - state.firstCrash > crashWindow) {
            state.count = 1;
            state.firstCrash = now;
        } else {
            state.count++;
        }

        if (state.count >= maxCrashes) {
            state.backoffUntil = now + backoffDuration;
        }

        saveState(state);
    },

    shouldReload() {
        const state = getState();
        if (state.backoffUntil && Date.now() < state.backoffUntil) {
            return false;
        }
        return true;
    },

    reset() {
        try { localStorage.removeItem(STORAGE_KEY); } catch {}
    },
};

export default crashGuard;
```

Create `assets/client/util/global-error-handlers.js`:

```js
import logger from '../logger/logger';
import crashGuard from './crash-guard';

let reloadDelay = 10_000; // Default — overridden by configure().

export function installGlobalErrorHandlers() {
    window.addEventListener('error', (event) => {
        logger.error('[global] Uncaught error:', event.error);
        handleFatalError();
    });

    window.addEventListener('unhandledrejection', (event) => {
        logger.error('[global] Unhandled rejection:', event.reason);
        handleFatalError();
    });
}

/** Call after config loads to override the default reload delay. */
export function configureGlobalErrorHandlers({ globalErrorReloadDelay }) {
    if (globalErrorReloadDelay) reloadDelay = globalErrorReloadDelay;
}

function handleFatalError() {
    crashGuard.recordCrash();

    if (crashGuard.shouldReload()) {
        setTimeout(() => window.location.reload(), reloadDelay);
    } else {
        logger.warn('[global] Crash loop detected — suppressing reload');
    }
}
```

Wire into `assets/client/app.jsx` useEffect:

```js
import { installGlobalErrorHandlers, configureGlobalErrorHandlers } from './util/global-error-handlers';
import crashGuard from './util/crash-guard';

useEffect(() => {
    installGlobalErrorHandlers();
    crashGuard.reset(); // Successful boot — reset the counter.

    ClientConfigLoader.loadConfig().then((config) => {
        // Push config values to error recovery modules.
        crashGuard.configure(config);
        configureGlobalErrorHandlers(config);
        // ... existing config handling ...
    });
}, []);
```

The `reset()` on successful boot breaks the crash-loop: if the app
managed to mount and start running, the previous crashes are forgiven.

Later in Step 15, the crash-loop guard will be enhanced to live in the
SW (survives main-thread death) using IndexedDB instead of localStorage.

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Global `window.onerror` and `unhandledrejection` handlers with crash-loop guard. Catches non-React errors and auto-reloads with backoff protection.

---

## Phase 3: Backend improvements

### Step 12 — Add API response headers via compiler pass

**Branch:** `feat/api-response-headers`

**Motivation:** The current client has two polling loops that have
nothing to do with actual content:

- `release.json` every 10 minutes — checks whether a new client build
  has been deployed so the browser can reload
- `/config/client` every 15 minutes — checks whether the backend's
  client config has changed

For a 200-screen deployment, that's 200 × 6 requests/hour for release
checking + 200 × 4 requests/hour for config checking = 2000 wasted
requests per hour. Each request costs a TCP handshake, TLS negotiation,
HTTP round trip, PHP-FPM process time, and a DB query (in the config
endpoint's case). All to discover "nothing changed" 99.9% of the time.

Worse, the release-check loop has a race condition. When it detects a
new build, it calls `window.location.replace()` to reload — but the
detection sits inside a `.finally()` that also triggers `checkLogin()`
as part of an unrelated path. The race: `.finally()` fires, `checkLogin()`
starts API calls, those API calls 401 because the deploy rotated some
keys, `reauthenticateHandler` wipes localStorage — AFTER the browser
has initiated the redirect but BEFORE navigation completes. The page
reloads with no auth, the client goes to the bind screen.

The cleaner model: the client is already making API calls for content
(every 90 seconds via pull-strategy). Those responses can carry
lightweight metadata telling the client when release or config
changes. Three headers:

- `X-OS2Display-Release-Timestamp` — unix timestamp of the current
  build, set at deploy time via env var
- `X-OS2Display-Release-Version` — the version string, for logging
- `X-OS2Display-Config-Hash` — 8-char hex hash of all client config
  env vars, computed at `cache:warmup`

Headers ride on every existing `/v2/` response. Zero new requests.
Detection happens at the cadence of existing traffic (90 seconds),
which is faster than the old 10-minute release check anyway.

Computing these values at runtime would mean reading env vars on every
response — cheap but not free, and the config hash would need
re-computation. The compiler pass computes all three values ONCE during
`cache:warmup` and bakes them into the compiled container as plain
string parameters. The subscriber injects the headers with a simple
`$response->headers->set()` call. Zero computation overhead at runtime.

Step 16 wires the SW to read these headers and notify the main thread.
Step 17 removes the old polling loops entirely.

**RED — write tests:**

File: `tests/EventSubscriber/ApiResponseHeaderSubscriberTest.php`

1. GET request to any `/v2/` endpoint returns all three `X-OS2Display-*` headers.
2. Request to `/admin` does NOT contain the headers.
3. `X-OS2Display-Config-Hash` is a valid 8-char hex string.
4. Hash changes when a config env var changes (boot two kernels, compare).

File: `tests/DependencyInjection/Compiler/ApiResponseHeaderPassTest.php`

1. Compiler pass sets three `app.api_header.*` container parameters.
2. Config hash is deterministic (same input → same output).

```bash
docker compose run --rm phpfpm composer test -- --filter=ApiResponseHeader
```

**GREEN — implement:**

1. Create `src/DependencyInjection/Compiler/ApiResponseHeaderPass.php`
2. Create `src/EventSubscriber/ApiResponseHeaderSubscriber.php`
3. Register pass in `src/Kernel.php` via `build()` method
4. Add service definition in `config/services.yaml`
5. Add `APP_RELEASE_TIMESTAMP=0` and `APP_RELEASE_VERSION=""` to `.env`
   and `.env.test`
6. Update `expose_headers` in `config/packages/nelmio_cors.yaml`

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Added: API responses include `X-OS2Display-Release-Timestamp`, `X-OS2Display-Release-Version`, and `X-OS2Display-Config-Hash` headers. Values are computed once at cache warmup via a compiler pass.

---

### Step 13 — Move PHP sessions to Redis

**Branch:** `feat/redis-sessions`

**Bug:** Symfony uses the default PHP session handler, which stores
session data as files in the container's filesystem (typically under
`/tmp/sess_*`). Session files are created on first use, read on
subsequent requests bearing the session cookie, and garbage-collected
after their TTL.

Container filesystems are ephemeral in every modern deployment strategy.
Any of the following events deletes the session files:

- Container restart (new deployment, OOM kill, failed health check,
  node drain)
- Container replacement (horizontal scaling event, rolling update)
- Manual recreation for any reason
- Kubernetes pod eviction

Sessions are critical to the screen binding flow. The sequence:

1. Unbound screen POSTs to `/v2/authentication/screen`
2. Backend creates a session, stores a bind key in it, returns the key
   and sets a session cookie on the response
3. Screen displays the bind key, polls `GET /v2/authentication/screen/status`
   every few seconds using the session cookie
4. Admin enters the bind key in the UI
5. Backend writes tokens to the cache against the session's bind key
6. Screen's next poll retrieves the tokens (Step 2's cache issue is a
   separate concern)

If the container restarts between steps 1 and 6 — common during any
deployment — the session file is gone. The screen's polling requests
arrive with a cookie for a session that no longer exists. Backend
treats this as "new session" and creates a fresh one with no bind key.
The screen's polling receives a state that doesn't match what it was
doing moments ago.

In multi-replica deployments (behind a load balancer), the problem is
worse: session files are local to one container, but different requests
from the same screen may hit different containers. Without session
affinity or shared storage, binding is essentially random.

The fix routes sessions through Redis via
`handler_id: '%env(REDIS_CACHE_DSN)%'`. Sessions survive container
restarts, work correctly across multiple backend replicas, and require
no new infrastructure — Redis is already in the stack for caching.

**RED — write test:**

File: `tests/Security/SessionPersistenceTest.php`

1. Create screen client, call `POST /v2/authentication/screen`, get bind key.
2. Clear filesystem session storage directory.
3. Same request with same session cookie.
4. Assert bind key is unchanged.

Currently fails because filesystem sessions are lost.

Note: the test environment uses `session.storage.factory.mock_file` per
`config/packages/test/framework.yaml` — the test may need an override
or a custom test kernel to exercise Redis sessions.

```bash
docker compose run --rm phpfpm composer test -- --filter=SessionPersistenceTest
```

**GREEN — fix:**

In `config/packages/framework.yaml`, change:
```yaml
session:
    handler_id: '%env(REDIS_CACHE_DSN)%'
    cookie_secure: auto
    cookie_samesite: lax
```

Ensure the `redis` service is in `docker-compose.override.yml` (already
present).

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Changed: PHP sessions now stored in Redis to survive container restarts.

---

## Phase 4: Frontend improvements

### Step 14 — Add IndexedDB auth store (auth-bridge)

**Branch:** `feat/auth-bridge-indexeddb`

**Motivation:** The client currently stores all auth state — JWT,
refresh token, screen ID, tenant key — in localStorage. localStorage is
convenient but has properties that hurt kiosk deployments:

- **Main-thread only.** Service workers cannot access localStorage.
  Step 15's SW takes over token refresh and auth injection, which
  requires the SW to read and write auth state directly. With
  localStorage, the SW would need to postMessage the main thread for
  every operation — slow, race-prone, and impossible during main-thread
  crashes (the exact scenario the SW watchdog is designed for).

- **Evictable under memory pressure.** Browsers with tight memory
  budgets (cheap ARM kiosks, Chrome OS devices, Android tablets) can
  evict localStorage during the "reset" phase of aggressive memory
  reclamation. The exact moment the client is fighting to stay alive
  is when it's most likely to lose its credentials.

- **Synchronous API blocks the main thread.** Every read/write pauses
  rendering. On slow storage (eMMC on old hardware), this can stall
  animations.

- **5MB limit shared with everything.** Template-generated data, logger
  buffers, and auth state all compete for the same quota. Auth can
  silently fail writes.

IndexedDB solves all four: SW-accessible, survives memory pressure
better than localStorage, async API doesn't block, quotas are measured
in gigabytes. Moving auth to IndexedDB isn't just a storage upgrade —
it's a prerequisite for the SW-based auth architecture.

The auth-bridge pattern keeps backward compatibility and eases the
migration:

1. On first load after the upgrade, `load()` checks IndexedDB first.
2. If IndexedDB is empty, it reads localStorage and migrates
   (write to IDB, keep in localStorage as mirror).
3. Subsequent loads find IDB populated and return from there.
4. Writes go to both stores during a transition period, so rolling back
   to the old client works.

Step 15's SW reads from IndexedDB on activation to have credentials
ready for the first fetch. Step 15 can also remove the localStorage
mirror once the migration period is done (next major version).

**RED — write test:**

File: `assets/client/util/__tests__/auth-bridge.test.js`

Uses `fake-indexeddb` (installed in Step 0).

1. `setCredentials()` writes to both IndexedDB and localStorage.
2. `load()` reads from IndexedDB first.
3. `load()` migrates from localStorage when IndexedDB is empty.
4. `clear()` removes from both stores.
5. `load()` after `clear()` returns empty credentials.
6. `hasCredentials()` reflects current state correctly.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — implement:**

1. Create `assets/client/util/auth-bridge.js`
2. Wire into `assets/client/app.jsx` — replace `appStorage.getToken()` /
   `appStorage.getScreenId()` reads in `checkLogin` with
   `authBridge.hasCredentials()` / `authBridge.getScreenId()`.
3. Replace `appStorage.setToken()` / etc. writes in the login flow with
   `authBridge.setCredentials()`.
4. Keep `appStorage` for non-auth data (fallback image, previous boot).

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Auth credentials stored in IndexedDB (persistent across browser restarts) with localStorage fallback and migration.

---

### Step 15 — Add service worker (auth + watchdog + fetch interception)

**Branch:** `feat/service-worker`

**Motivation:** The main thread has always been a single point of
failure. When it dies — stuck in a JavaScript deadlock, paused by a
blocking synchronous operation, crashed by a memory-corruption bug in a
template, deadlocked on a race between React and a template library,
or just running slowly enough to miss polling ticks — nothing in the
client can detect or recover from it. The main thread is both the
patient and the doctor.

A service worker runs in a separate thread with its own event loop.
It keeps running when the main thread is stuck. This changes the
recovery architecture fundamentally:

**Watchdog pattern.** Main thread sends a heartbeat to the SW every
`heartbeatInterval` ms. If the SW doesn't hear from the main thread
for `watchdogTimeout` ms, the main thread is considered dead. The SW
calls `client.navigate(client.url)` on the controlled window, forcing
a navigation (equivalent to a reload). This recovers from conditions
that would otherwise permanently hang a kiosk. Without a SW, the only
way to detect main-thread death is a human walking up to the screen.

**Centralized auth.** The current auth lifecycle spans `app.jsx`,
`tokenService.js`, `api-helper.js`, and `appStorage.js`, with implicit
contracts between them:
- `tokenService.startRefreshing()` polls `/token/refresh` every 7.5 days
- `api-helper.js` catches 401s, dispatches `reauthenticate` events
- `app.jsx` listens for `reauthenticate`, wipes storage, restarts login
- Every API call reads the token from `appStorage` and sets the header

This architecture has 5 bugs that this plan fixes individually (Steps 5,
6, 9, etc.). But the architecture itself keeps inviting bugs because
auth is a cross-cutting concern scattered across modules. Moving it
into the SW:

- SW intercepts every `/v2/` fetch, injects the auth header
- SW sees 401 responses, refreshes the token transparently, retries
  the original request with the new token
- SW manages the refresh cycle internally (no polling in the main
  thread)
- Main thread never sees 401s except for genuinely unrecoverable auth
  failures, in which case the SW posts `tokenRefreshFailed` and the
  main thread shows the fallback image and starts the re-bind flow

Five files become one module (plus a thin `sw-registration.js`).

**Response header detection (Step 16).** The SW is also the natural
place to observe response headers. Step 16 wires it to watch
`X-OS2Display-Release-Timestamp` and `X-OS2Display-Config-Hash` headers
from existing API responses; on change, it posts to the main thread.
This replaces the polling loops removed in Step 17.

**Crash-loop upgrade.** The SW's crash-guard module upgrades Step 11c's
localStorage-based guard to IndexedDB. The SW can observe reload
cycles directly (by counting `activate` events, or by tracking
`client.navigate()` calls) and participate in the cross-thread crash
loop detection — the main-thread handler records crashes, the SW
watchdog also records navigation reloads, and the guard sees the
combined picture.

**Modular source, single output.** The SW is conceptually five small
responsibilities — auth state, fetch interception, watchdog, crash
guard, and message routing. Source code lives in six files under
`assets/client/sw/`, organized by responsibility. esbuild bundles them
into one IIFE at `public/client/sw.js` for the browser. Maintainability
and runtime performance both win.

**RED — write test:**

File: `assets/client/service/__tests__/sw-registration.test.js` (JS)

1. `registerSW()` calls `navigator.serviceWorker.register` with
   URL `/client/sw.js` and `updateViaCache: 'none'`.
2. `onTokenRefreshed` callback fires when SW posts `tokenRefreshed`.
3. `onTokenRefreshFailed` callback fires on failure message.
4. Heartbeat interval is started.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

File: `assets/client/sw/__tests__/lifecycle.test.js`

1. Mock `self.skipWaiting` and `self.clients.claim`.  Import sw/index.js
   (which installs the listeners at module load).  Dispatch an `install`
   event.  Assert `self.skipWaiting` was called.
2. Mock `loadAuth` to resolve after a short delay.  Dispatch an `activate`
   event.  Assert:
   - `loadAuth` was called
   - `self.clients.claim` was called AFTER `loadAuth` resolved (not before)
   - `event.waitUntil` received the combined promise
3. `loadAuth` rejects (IndexedDB corrupted or unavailable).  Assert:
   - `self.clients.claim` IS still called — the SW must take control
     so the main thread receives a signal rather than silently running
     without SW support
   - A `tokenRefreshFailed` message is posted to controlled clients
     so the main thread enters the re-bind flow via existing error
     handling
   - The `activate` event's promise resolves rather than rejecting
     (a rejected waitUntil terminates the SW)

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

File: `assets/tests/client/client-sw.spec.js` (Playwright)

1. After login, API calls succeed (SW injects auth headers).
2. Simulated 401 → 200 retry is transparent.

**GREEN — implement:**

Build pipeline:

1. Create modular SW source under `assets/client/sw/`:
   ```
   assets/client/sw/
     index.js          ← entry point, imports all modules
     auth.js           ← token state, IndexedDB persistence, refresh loop
     fetch.js          ← fetch interception, 401 retry, response header detection
     watchdog.js       ← heartbeat monitoring, client.navigate() recovery
     crash-guard.js    ← crash-loop tracking (upgrades Step 11c from localStorage to IndexedDB)
     idb.js            ← shared IndexedDB open/read/write helpers
     messages.js       ← message type constants shared with main thread
   ```
   esbuild bundles all modules into one IIFE output — the SW runtime is
   a single file, but the source is maintainable.

2. Create `vite-plugin-sw.js` — Vite plugin that builds the SW via esbuild
   in the `closeBundle` hook.  Entry point: `assets/client/sw/index.js`.
   Outputs to `public/client/sw.js`.
   Scope defaults to `/client/` which covers the client page — all fetch
   requests from controlled pages (including `/v2/` API calls) go through
   the SW.  Nginx serves as a static file.
   In dev mode, serves a dev build via Vite's dev server middleware.
3. Update `vite.config.js` — import and register `serviceWorkerPlugin()`.
4. Add `public/client/sw.js` to `.gitignore` (build output).
5. Build to verify:
   ```bash
   docker compose run --rm node npm run build
   ls -la public/client/sw.js  # should exist, minified
   ```

SW lifecycle handlers (required — determines how fast updates take
effect):

6. `assets/client/sw/index.js` must register `install` and `activate`
   handlers so updated SW code (including watchdog logic) takes effect
   immediately without waiting for a navigation cycle:

   ```js
   self.addEventListener("install", () => {
     // Skip the "waiting" state — don't wait for the old SW to release
     // its clients.  Without this, an updated SW sits inactive until
     // the kiosk navigates away, which for a 24/7 signage screen may
     // never happen.
     self.skipWaiting();
   });

   self.addEventListener("activate", (event) => {
     // Two things happen in the activate handler:
     //
     // 1. loadAuth() restores credentials from IndexedDB into the SW's
     //    in-memory state.  This SHOULD complete before clients.claim()
     //    because claim() starts routing fetches through the SW, and
     //    the fetch interceptor needs auth state available.
     //
     // 2. clients.claim() takes control of all in-scope tabs/clients
     //    that loaded before this SW version existed.  Without it,
     //    already-open clients continue using the old SW (or no SW
     //    at all for a fresh install) until they navigate.
     //
     // If loadAuth() rejects (IndexedDB corrupted), claim anyway and
     // signal the main thread to re-bind.  Silently not claiming would
     // leave the kiosk running without SW support and no signal that
     // something went wrong.
     //
     // waitUntil() keeps the activate event alive until the promise
     // resolves, preventing the browser from terminating the SW mid-
     // initialization.  We catch loadAuth failures so the outer
     // promise always resolves; a rejected waitUntil terminates the SW.
     event.waitUntil(
       loadAuth()
         .catch((err) => {
           // Signal clients to re-bind — they'll reach STATUS_LOGIN and
           // restart the bind flow via existing error paths.
           notifyAllClients({ type: messageTypes.TOKEN_REFRESH_FAILED });
         })
         .then(() => self.clients.claim())
     );
   });
   ```

   The ordering matters: on a fresh install, `clients.claim()` fires the
   `controllerchange` event on the main thread, which the main thread
   uses as the signal that the SW is ready to receive `postMessage`.
   If we claimed before loading auth, the main thread could send the
   first `setVitals` / `setBootSession` message, have the SW try to
   inject auth headers on its next fetch, and find no token because
   `loadAuth()` hasn't completed yet.

Client integration:

6. Create `assets/client/service/sw-registration.js` — registers the SW
   at `/client/sw.js` with `updateViaCache: 'none'` (bypasses HTTP cache for
   update checks — browsers cap SW cache at 24h anyway, this makes it
   immediate).  Starts heartbeat.  Handles messages from SW.
7. Wire into `assets/client/app.jsx` — call `registerSW()` after
   `authBridge.load()`.
8. Remove `tokenService.startRefreshing()` / `stopRefreshing()`.
9. Remove `reauthenticateHandler` event listener.  Add
   `handleRefreshFailure` for the `onTokenRefreshFailed` callback.
10. Remove auth header injection from `assets/client/data-sync/api-helper.js`.
11. Remove `reauthenticate` event dispatch from `api-helper.js`.

**Verify:**
```bash
docker compose run --rm node npm run build
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Service worker handles token refresh, 401 retry, auth header injection, and main-thread watchdog. Built by Vite plugin, served as static file by nginx.

---

### Step 16 — Integrate response header change detection

**Branch:** `feat/sw-response-header-detection`

**Depends on:** Step 12 (backend headers) and Step 15 (service worker).

**Motivation:** Step 12 added the headers. Step 15 built the SW. This
step wires them together.

The SW's `fetch` handler already intercepts every `/v2/` response to
inject auth. It's the perfect place to also inspect the response
headers for change markers. On every response:

1. Read `X-OS2Display-Release-Timestamp`, `X-OS2Display-Release-Version`,
   `X-OS2Display-Config-Hash`.
2. Compare with the last-seen values (stored in SW memory + IndexedDB).
3. If release timestamp changed → post `onReleaseChanged` to main thread.
4. If config hash changed → post `onConfigChanged` to main thread.

The main thread reacts:

- `onReleaseChanged` → `window.location.replace()` to pick up the new
  build. Auth persists (IndexedDB survives navigation). No race
  condition because the SW is authoritative — no separate redirect
  path fights with auth handling.
- `onConfigChanged` → `ClientConfigLoader.invalidateCache()` so the
  next config read fetches fresh values from `/config/client`.

Header inspection is essentially free — the SW is already reading every
response. Comparing 3 strings adds microseconds per response. No new
network traffic, no new polling loops, no extra PHP overhead.

Contrast with the current polling model: 2 separate intervals, 2
separate endpoints, 2 separate race conditions, runs on every screen
regardless of whether anything changed. This step replaces both with
observation of existing traffic.

**RED — write test:**

File: `assets/tests/client/client-release-detection.spec.js` (Playwright)

1. Mock API to return changed `X-OS2Display-Release-Timestamp` on second
   response.  Assert the client reloads.
2. Mock changed `X-OS2Display-Config-Hash`.  Assert config is re-fetched.

```bash
docker compose run --rm playwright npx playwright test
```

**GREEN — implement:**

1. Add `checkResponseHeaders()` to `public/client/sw.js` fetch handler.
2. Add `onReleaseChanged` / `onConfigChanged` to `sw-registration.js`.
3. Wire `onReleaseChanged` in `app.jsx` to `window.location.replace()`.
4. Wire `onConfigChanged` in `app.jsx` to `ClientConfigLoader.invalidateCache()`.
5. Add `invalidateCache()` method to `assets/client/util/client-config-loader.js`.

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Release and config changes detected via API response headers in the service worker.

---

### Step 17 — Remove old polling loops

**Branch:** `feat/remove-polling-loops`

**Depends on:** Step 16.

**Motivation:** With Step 16 in place, the old release and config
polling mechanisms are redundant — the SW now detects changes faster,
cheaper, and without race conditions. Time to remove them.

The removals are surgical:

- `releaseService.startReleaseCheck()` / `stopReleaseCheck()` — the
  10-minute release polling interval. Delete.
- `releaseService.checkForNewRelease()` — the function that runs on
  boot, fetches `release.json`, and conditionally redirects. Delete.
  This also eliminates the race condition documented in Step 12: the
  redirect no longer fires during boot, so it can't fight with
  `checkLogin()`'s 401 handling.
- `releaseService.setPreviousBootInUrl()` — the hack that set a query
  parameter on the redirect target to prevent redirect loops. No
  redirect → no loop → no query parameter.
- `ClientConfigLoader`'s internal cache timer — the 15-minute interval
  that marked the config as stale. Cache invalidation now happens
  externally via `invalidateCache()` called by the SW message handler.

The boot sequence simplifies from:

```js
// Before
appStorage.load()
  → if (shouldRedirect) window.location.replace()
  → .finally(() => { checkLogin(); startReleaseCheck(); ... })
```

To:

```js
// After
authBridge.load()
  .then(() => {
    registerSW({ onTokenRefreshed, onReleaseChanged, onConfigChanged });
    checkLogin();
  });
```

Four fewer files involved, one fewer race condition, two fewer polling
loops, no redirect-on-boot timing problems. Every concern that used to
be split between a polling loop and an event handler is now unified
under the SW's fetch interception.

**RED — write test:**

File: `assets/client/service/__tests__/boot-sequence.test.js`

1. Assert `releaseService.startReleaseCheck` is NOT called during boot.
2. Assert `checkForNewRelease()` redirect is NOT called during boot.
3. Assert boot goes: `authBridge.load()` → `registerSW()` → `checkLogin()`
   with no redirect.

```bash
docker compose run --rm node npx vitest run --reporter=verbose
```

**GREEN — implement:**

1. Remove `releaseService.checkForNewRelease()` from `app.jsx` useEffect.
2. Remove `releaseService.startReleaseCheck()` / `stopReleaseCheck()` calls.
3. Remove `releaseService.setPreviousBootInUrl()`.
4. Simplify boot:
   ```js
   authBridge.load().then(() => {
     registerSW({ ... });
     checkLogin();
     appStorage.setPreviousBoot(new Date().getTime());
   });
   ```
5. Remove internal cache timer from `ClientConfigLoader`.

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Removed: Dedicated polling loops for release.json and config.json replaced by API response header detection via service worker.

---

### Step 17a — Preserve TRACK_SCREEN_INFO via request headers

**Branch:** `feat/track-screen-info-headers`

**Depends on:** Steps 12, 15, 17.

**Motivation:** `TRACK_SCREEN_INFO` is a backend feature that captures
per-screen diagnostics — release version, latest request timestamp,
client IP, user agent, token expiry state — on every API call a screen
makes. Admins query this data via `GET /v2/screens/{id}` to answer "which
build is each screen running?" and "when was this screen last seen?"
It's disabled by default (`TRACK_SCREEN_INFO=false`) but is actively
used in deployments where fleet monitoring matters.

The release info specifically (`releaseVersion`, `releaseTimestamp`) is
captured via an indirect path:

1. `releaseService.checkForNewRelease()` fetches `release.json`
2. On version change, it redirects to the same URL with
   `?releaseTimestamp=X&releaseVersion=Y` appended
3. The browser remembers this URL. Every subsequent API call includes
   it as the `Referer` header
4. `ScreenUserRequestSubscriber` reads the Referer, parses its query
   string, extracts the two values, writes them to the ScreenUser entity

Step 17 deletes `releaseService` entirely. The URL never gets those
params. The Referer stops carrying them. The subscriber still runs on
every `/v2/screens/{ulid}` request (gated by `trackScreenInfo && path
match`), still parses the Referer, but now always finds empty values.
ScreenUser's `releaseVersion` and `releaseTimestamp` fields get
overwritten with `null` on every request. TRACK_SCREEN_INFO silently
stops working — ops teams see "no data" in the admin UI and assume
screens are offline, when they're actually running normally.

The data flow was always fragile: passing structured telemetry through
a URL query parameter that exists only because of a redirect side
effect, extracted via Referer parsing, is indirect at best. The fix
replaces it with explicit request headers:

- Client knows its own build info (injected by Vite at build time)
- SW adds `X-OS2Display-Client-Release-Version` and
  `X-OS2Display-Client-Release-Timestamp` to every `/v2/` request
- Subscriber reads from these headers, ignores Referer entirely

Direct, unambiguous, robust to URL changes.

**RED — write tests:**

File: `tests/EventSubscriber/ScreenUserRequestSubscriberHeadersTest.php`

1. Send a request to `/v2/screens/{ulid}` with
   `X-OS2Display-Client-Release-Version: v3.0.0` and
   `X-OS2Display-Client-Release-Timestamp: 1234567890` headers (no
   Referer).  Assert ScreenUser's fields are updated.
2. Send a request with ONLY the Referer-style params (legacy client
   before this step deployed).  Assert the fields are still updated —
   backward compatibility during the upgrade window.
3. Send a request with BOTH headers and Referer params.  Assert the
   header values win (newer client).
4. Assert subscriber still gates on
   `$trackScreenInfo && preg_match('/^\/v2\/screens\/.../...)` —
   disabled deployments still skip entirely.

File: `assets/client/sw/__tests__/request-headers.test.js`

1. Call the SW's fetch handler with a request to `/v2/screens/abc`.
2. Assert the outgoing request has
   `X-OS2Display-Client-Release-Version` and
   `X-OS2Display-Client-Release-Timestamp` headers.
3. Assert the values match the build-time constants.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenUserRequestSubscriberHeadersTest
docker compose run --rm node npx vitest run
```

**GREEN — implement:**

**Step A — Vite build-time injection:**

In `vite.config.js`, inject release info as build-time constants:

```diff
 export default defineConfig(() => {
   return {
     base: "/build",
+    define: {
+      __RELEASE_TIMESTAMP__: JSON.stringify(
+        process.env.APP_RELEASE_TIMESTAMP || "0"
+      ),
+      __RELEASE_VERSION__: JSON.stringify(
+        process.env.APP_RELEASE_VERSION || "dev"
+      ),
+    },
     // ... existing config ...
   };
 });
```

These match the backend env vars introduced in Step 12 so client and
backend stay in sync when deployed together.

**Step B — SW injects headers:**

In `assets/client/sw/fetch.js`, add the two headers to every outgoing
`/v2/` request alongside the auth header:

```js
// Build-time constants — replaced by Vite at bundle time.
const CLIENT_RELEASE_VERSION = __RELEASE_VERSION__;
const CLIENT_RELEASE_TIMESTAMP = __RELEASE_TIMESTAMP__;

function injectHeaders(request, token) {
    const headers = new Headers(request.headers);
    if (token) {
        headers.set("Authorization", `Bearer ${token}`);
    }
    headers.set("X-OS2Display-Client-Release-Version", CLIENT_RELEASE_VERSION);
    headers.set("X-OS2Display-Client-Release-Timestamp", CLIENT_RELEASE_TIMESTAMP);

    return new Request(request, { headers });
}
```

**Step C — Subscriber reads from headers, falls back to Referer:**

In `src/EventSubscriber/ScreenUserRequestSubscriber.php`
`createCacheEntry`:

```php
$request = $event->getRequest();

// Prefer explicit headers (post-Step-17a clients).
$releaseVersion = $request->headers->get('X-OS2Display-Client-Release-Version');
$releaseTimestamp = $request->headers->get('X-OS2Display-Client-Release-Timestamp');

// Fall back to Referer parsing for legacy clients that haven't
// upgraded yet.  Remove this block in a later release once all
// clients in the deployment are on the new protocol.
if (null === $releaseVersion || null === $releaseTimestamp) {
    $referer = $request->headers->get('referer') ?? '';
    $url = parse_url($referer);
    $queryString = $url['query'] ?? '';
    $queryArray = [];

    if (!empty($queryString)) {
        parse_str($queryString, $queryArray);
    }

    $releaseVersion ??= $queryArray['releaseVersion'] ?? null;
    $releaseTimestamp ??= $queryArray['releaseTimestamp'] ?? null;
}

$screenUser->setReleaseTimestamp((int) $releaseTimestamp);
$screenUser->setReleaseVersion($releaseVersion);
// ... rest unchanged ...
```

**Step D — CORS allowlist:**

Update `config/packages/nelmio_cors.yaml` `allow_headers` to include the
two new request headers (alongside any existing auth headers):

```yaml
allow_headers:
    - 'Content-Type'
    - 'Authorization'
    - 'Authorization-Tenant-Key'
    - 'X-OS2Display-Client-Release-Version'
    - 'X-OS2Display-Client-Release-Timestamp'
```

Without this, the browser's preflight will reject requests carrying the
custom headers.

**Verify:**
```bash
docker compose run --rm node npm run build
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Changed: `TRACK_SCREEN_INFO` now captures release info via dedicated `X-OS2Display-Client-Release-*` request headers instead of parsing the Referer URL. Preserves telemetry functionality after the release-polling loop was removed. Referer-based fallback kept for one release cycle to support clients upgrading mid-deploy.

---

### Step 17b — Real-time screen last-seen via Redis

**Branch:** `feat/realtime-screen-last-seen`

**Depends on:** Step 1, Step 17a.

**Motivation:** `ScreenProvider` reads `latestRequest` from the
`ScreenUser` entity in the database. That field is updated by
`ScreenUserRequestSubscriber` — but only when the subscriber's Redis
cache entry misses, which is throttled to once per
`TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS` (default 300). Between
misses, no code path updates the timestamp anywhere: not the DB, not
the cache entry itself. `GET /v2/screens/{id}` therefore returns a
`latestRequestDateTime` that can be up to 5 minutes stale.

For fleet monitoring this is a meaningful gap. A screen that crashed 3
minutes ago still shows as "recently active." An admin investigating
"which screens are alive?" can't distinguish a working screen from one
that just crashed within the tracking interval. The DB throttling is
correct for heavy data (`clientMeta`, `releaseVersion`, IP, user
agent) — those rarely change and don't need per-request writes — but
`latestRequestDateTime` is specifically the field where freshness
matters most.

The fix separates two concerns:

- **Liveness heartbeat** — `latestRequestDateTime` updated on every
  tracked request via a lightweight Redis SET. No DB write. Essentially
  free (Redis handles millions of SETs/sec).
- **Durable metadata** — the existing 5-minute DB write pattern for
  `clientMeta`, `releaseVersion`, `releaseTimestamp`. Unchanged.

`ScreenProvider` reads the liveness key first; falls back to the
entity's `latestRequest` field if Redis is unavailable or the key
expired. Admins see request-level granularity (≈90s, matching the
pull-strategy poll interval) instead of 5-minute granularity.

Redis write volume estimate: 200 screens × 40 polls/hour = 8000
liveness writes/hour ≈ 2.2/sec. Negligible.

**RED — write tests:**

File: `tests/EventSubscriber/ScreenLastSeenCacheTest.php`

1. Send a request to `/v2/screens/{ulid}` with `TRACK_SCREEN_INFO=true`.
   Assert the Redis key `screen:last-seen:{ulid}` is set with a
   timestamp close to `now()`.
2. Send two requests 30 seconds apart.  Assert both update the Redis
   key.  Assert the DB's `latestRequest` field is unchanged between
   them (still throttled by the existing 5-minute interval).
3. Send a request with `TRACK_SCREEN_INFO=false`.  Assert no Redis key
   is written.
4. Send a request to a non-screen endpoint (e.g., `/v2/playlists`).
   Assert no Redis key is written (path check still gates).

File: `tests/State/ScreenProviderLastSeenTest.php`

1. Populate Redis with `screen:last-seen:{ulid}` set to `now()`.
   Populate ScreenUser's `latestRequest` with a value 10 minutes old.
   Call `ScreenProvider::provide()` via `GET /v2/screens/{ulid}`.
   Assert response `status.latestRequestDateTime` matches Redis, not DB.
2. Delete the Redis key (simulate TTL expiry or Redis eviction).
   Call provide again.  Assert response falls back to the DB value.
3. `TRACK_SCREEN_INFO=false`: assert the `status` field is absent
   from the response (existing behavior preserved).

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenLastSeen
docker compose run --rm phpfpm composer test -- --filter=ScreenProviderLastSeen
```

**GREEN — implement:**

**Step A — Update the subscriber to write the liveness key:**

In `src/EventSubscriber/ScreenUserRequestSubscriber.php`:

```php
public function onKernelRequest(RequestEvent $event): void
{
    $pathInfo = $event->getRequest()->getPathInfo();

    if (!$this->trackScreenInfo
        || !preg_match("/^\/v2\/screens\/[A-Za-z0-9]{26}$/i", $pathInfo)) {
        return;
    }

    $user = $this->security->getUser();
    if (!$user instanceof ScreenUser) {
        return;
    }

    $key = $user->getId()?->jsonSerialize();
    if (null === $key) {
        return;
    }

    // Liveness heartbeat: update on EVERY request.  Cheap Redis SET.
    $liveItem = $this->screenStatusCache->getItem($this->liveSeenKey($key));
    $liveItem->set((new \DateTime())->format('c'));
    // TTL slightly longer than one full tracking interval so the key
    // survives brief Redis maintenance windows and serves as a soft
    // TTL rather than an authoritative cutoff.
    $liveItem->expiresAfter($this->trackScreenInfoUpdateIntervalSeconds * 4);
    $this->screenStatusCache->save($liveItem);

    // Durable snapshot: unchanged 5-minute-throttled DB write.
    $this->screenStatusCache->get(
        $key,
        fn (CacheItemInterface $item) => $this->createCacheEntry($item, $event, $user)
    );
}

private function liveSeenKey(string $id): string
{
    return "last-seen.$id";
}
```

The existing `createCacheEntry` method keeps writing the DB on its
usual interval. No changes to that method's body (Step 1's
`detach($screenUser)` fix stays in place).

**Step B — ScreenProvider reads Redis first:**

In `src/State/ScreenProvider.php`, inject the cache and override the
timestamp:

```php
public function __construct(
    // ... existing deps ...
    private readonly CacheInterface $screenStatusCache,
    private readonly bool $trackScreenInfo = false,
) {}

// In provide() where status is computed:
if ($this->trackScreenInfo) {
    $screenUser = $object->getScreenUser();
    $status = null;

    if (null != $screenUser) {
        $status = $this->getStatus($screenUser);

        // Override with real-time liveness if available.
        $screenUserId = $screenUser->getId()?->jsonSerialize();
        if (null !== $screenUserId) {
            $liveItem = $this->screenStatusCache->getItem("last-seen.$screenUserId");
            if ($liveItem->isHit()) {
                $status['latestRequestDateTime'] = $liveItem->get();
            }
        }
    }

    $output->status = $status;
}
```

The `isHit()` check handles the fallback cleanly: Redis down → no hit →
DB value is used (current behavior preserved). Redis up → fresh
timestamp.

**Step C — Update service config:**

In `config/services.yaml`, inject the cache into ScreenProvider:

```yaml
App\State\ScreenProvider:
    arguments:
        # ... existing args ...
        $screenStatusCache: '@screen.status.cache'
```

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Changed: Screen `latestRequestDateTime` now updates in Redis on every tracked request, giving admins poll-interval freshness (~90s) instead of the previous 5-minute granularity. Durable metadata fields (clientMeta, releaseVersion) still throttled to the configured DB write interval. Falls back to DB value if Redis is unavailable.

---

### Step 17c — Skip relations checksum recomputation on irrelevant flushes

**Branch:** `fix/checksum-skip-irrelevant-flush`

**Bug:** `RelationsChecksumListener` subscribes to Doctrine's `postFlush`
event and, when `RELATIONS_CHECKSUM_ENABLED=true`, unconditionally runs
`$this->calculator->execute(withWhereClause: true)` on every flush.

The calculator executes ~30 SQL statements in a transaction: a set of
`UPDATE parent JOIN child ... WHERE p.changed = 1 OR temp.changed = 1`
propagation queries across 15 checksum-tracked tables, plus
`UPDATE table SET changed = 0 WHERE changed = 1` reset queries.

The listener itself only marks entities as `changed = 1` when those
entities implement `RelationsChecksumInterface` (Screen, Playlist, Slide,
Media, etc.). `ScreenUser` does NOT implement that interface. When
`ScreenUserRequestSubscriber` flushes (every 5 minutes per tracked
screen), only ScreenUser's own fields change — nothing marks any
checksum-tracked entity as changed.

But `postFlush` runs anyway. The calculator executes all ~30 UPDATE
statements. Every one matches zero rows because nothing has
`changed = 1`. The DB parses each query, plans it, scans the
`changed` index, commits a transaction — for zero data changes.

At 200 screens × 12 flushes/hour × 30 queries = **72 000 no-op
queries/hour**. MariaDB handles them, but it's measurable load for zero
benefit. On a loaded instance, these pointless transactions compete
with real queries for the connection pool and binlog.

The same pattern applies to any flush that doesn't touch a checksum
entity: user profile updates, refresh token saves, session writes
through Doctrine, any subscriber modifying non-tracked entities. Every
one incurs the full recomputation pass.

The fix: track within the listener's lifecycle whether any
`RelationsChecksumInterface` entity was actually flagged `changed`
during the current unit of work.  If nothing was flagged, `postFlush`
has nothing to propagate — skip the calculator entirely.

**RED — write test:**

File: `tests/EventListener/RelationsChecksumListenerSkipTest.php`

1. Flush a ScreenUser update (no relations-tracked entity modified).
   Use a DB query counter (Doctrine SQLLogger or middleware) to assert
   the calculator's SQL was NOT executed — 0 UPDATE queries on the
   checksum tables.
2. Flush a Screen update (entity implements `RelationsChecksumInterface`).
   Assert the calculator DID run — UPDATE queries on the checksum tables
   were issued.
3. Flush a mixed batch (ScreenUser + Screen in the same unit of work).
   Assert the calculator ran (one changed entity is enough).
4. Flush with `RELATIONS_CHECKSUM_ENABLED=false` and a Screen update.
   Assert the calculator did NOT run (existing behavior preserved).

```bash
docker compose run --rm phpfpm composer test -- --filter=RelationsChecksumListenerSkipTest
```

**GREEN — fix:**

In `src/EventListener/RelationsChecksumListener.php`, add a flag
that tracks whether any tracked entity was modified during the
current unit of work:

```php
class RelationsChecksumListener
{
    private bool $shouldRecompute = false;

    public function __construct(
        private readonly RelationsChecksumCalculator $calculator,
        private readonly bool $enabled = false,
    ) {}

    final public function prePersist(PrePersistEventArgs $args): void
    {
        if (!$this->enabled) return;

        $entity = $args->getObject();
        // ... existing switch statement setting initial checksums ...

        // If the switch matched a tracked entity type, a new row will
        // be inserted → checksum propagation needed.
        if ($entity instanceof RelationsChecksumInterface) {
            $this->shouldRecompute = true;
        }
    }

    final public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$this->enabled) return;

        $entity = $args->getObject();
        if ($entity instanceof RelationsChecksumInterface) {
            $entity->setChanged(true);
            $this->shouldRecompute = true;
        }
    }

    final public function preRemove(PreRemoveEventArgs $args): void
    {
        if (!$this->enabled) return;

        // ... existing switch statement marking parents as changed ...
        // Every setChanged(true) path also sets $this->shouldRecompute.

        switch ($entity::class) {
            case ScreenLayoutRegions::class:
                $entity->getScreenLayout()?->setChanged(true);
                $this->shouldRecompute = true;
                break;
            // ... other cases, same pattern ...
        }
    }

    final public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled) return;

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $owner = $collection->getOwner();
            if ($owner instanceof RelationsChecksumInterface) {
                $owner->setChanged(true);
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata($owner::class),
                    $owner
                );
                $this->shouldRecompute = true;
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $owner = $collection->getOwner();
            if ($owner instanceof RelationsChecksumInterface) {
                $owner->setChanged(true);
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata($owner::class),
                    $owner
                );
                $this->shouldRecompute = true;
            }
        }
    }

    final public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->enabled) return;

        // Skip the expensive recomputation pass if nothing relevant
        // actually changed during this unit of work.
        if (!$this->shouldRecompute) {
            return;
        }

        try {
            $this->calculator->execute(withWhereClause: true);
        } finally {
            $this->shouldRecompute = false;
        }
    }
}
```

The flag is reset inside a `finally` so a calculator exception doesn't
leave the listener in a stuck `shouldRecompute=true` state that would
force the next (unrelated) flush to recompute too.

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Fixed: `RelationsChecksumListener` no longer runs the ~30-query propagation pass on flushes that didn't modify any checksum-tracked entity. Previously fired on every Doctrine flush regardless of relevance, producing tens of thousands of no-op queries per hour under TRACK_SCREEN_INFO load.

---

### Step 17d — Only write DB on heavy metadata change

**Branch:** `feat/tracking-diff-aware-writes`

**Depends on:** Step 1, Step 17a, Step 17b.

**Motivation:** After Step 17b moved `latestRequestDateTime` to Redis,
the DB write in `ScreenUserRequestSubscriber::createCacheEntry` only
needs to persist the heavy metadata: `releaseVersion`, `releaseTimestamp`,
`clientMeta` (host, userAgent, ip, tokenExpired).

Those values rarely change for a steady-state screen:

- `releaseVersion` / `releaseTimestamp` — only on deploy
- `userAgent` — only on browser upgrade (essentially never on kiosk hardware)
- `host` — the client's URL; static
- `ip` — occasional (DHCP lease change, VLAN reassignment); rare
- `tokenExpired` — computed as `$exp < $now`; for authenticated requests
  the token is always valid, so this is always false. The only way to
  reach the subscriber with an expired token would bypass the JWT
  authenticator, which isn't possible

The current subscriber still does a DB flush on every 5-minute cache
miss, writing identical values for every field other than
`latestRequest` — which is no longer needed in the DB post-17b.

Even with Doctrine's change tracking detecting no-op UPDATEs at the SQL
level, the flush path still:

- Computes the UoW change set (scans all loaded entities)
- Fires postFlush events (Step 17c helps but pre-17c is significant)
- Acquires a connection from the pool
- Starts/commits a transaction even if empty

The fix compares each incoming value against the entity's current
state and skips the flush entirely if nothing differs. For a stable
screen between deploys, the subscriber does ZERO DB writes per day.

Additional discovery: `$screenStatusCache` is never read outside the
subscriber — grep confirms only one call site. The cache exists purely
as a throttle (cache-miss pattern rate-limits the callback), and the
callback's return value is discarded. This means we can freely change
the callback's internal logic without affecting any reader.

**Write volume estimates** (200 screens, weekly deploy cycle):

| Stage | DB writes / week |
|---|---|
| Before any fixes | ~1,680,000 (5-min × 200 screens × 168 hours × 12) |
| After Step 1 (clear→detach) | same write count, less entity damage |
| After Step 17c (skip checksum) | same write count, fewer post-flush queries |
| After Step 17d (diff-aware) | **~200–500** (only on deploys + rare IP/UA changes) |

**RED — write tests:**

File: `tests/EventSubscriber/ScreenUserDiffAwareWritesTest.php`

1. Populate a ScreenUser with fixed releaseVersion/clientMeta values.
   Make a tracked request with headers/Referer carrying the SAME values.
   Use Doctrine SQLLogger to assert NO `UPDATE screen_user` query fired.
2. Make a request with a different releaseVersion.  Assert UPDATE fires.
   Assert new value persisted.
3. Make a request with a different IP (everything else same).  Assert
   UPDATE fires with new clientMeta.
4. Make a request with nothing changed, but wait for the cache TTL to
   expire, make another identical request.  Assert still no UPDATE
   (throttle doesn't matter; comparison does).
5. First tracked request after a screen's creation (ScreenUser has
   null clientMeta and null release fields).  Assert UPDATE fires and
   populates the fields.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenUserDiffAwareWritesTest
```

**GREEN — implement:**

Replace the body of
`src/EventSubscriber/ScreenUserRequestSubscriber::createCacheEntry`:

```php
private function createCacheEntry(
    CacheItemInterface $item,
    RequestEvent $event,
    ScreenUser $screenUser
): array {
    $item->expiresAfter($this->trackScreenInfoUpdateIntervalSeconds);

    $request = $event->getRequest();

    // Extract new metadata — headers first (Step 17a), Referer fallback.
    $releaseVersion = $request->headers->get('X-OS2Display-Client-Release-Version');
    $releaseTimestamp = $request->headers->get('X-OS2Display-Client-Release-Timestamp');

    if (null === $releaseVersion || null === $releaseTimestamp) {
        $referer = $request->headers->get('referer') ?? '';
        $parsed = parse_url($referer);
        $queryString = $parsed['query'] ?? '';
        $queryArray = [];
        if (!empty($queryString)) {
            parse_str($queryString, $queryArray);
        }
        $releaseVersion ??= $queryArray['releaseVersion'] ?? null;
        $releaseTimestamp ??= $queryArray['releaseTimestamp'] ?? null;
    }

    $userAgent = $request->headers->get('user-agent') ?? '';
    $ip = $request->getClientIp();
    $host = preg_replace("/\?.*$/i", '', $request->headers->get('referer') ?? '');

    $tokenExpired = false;
    $token = $this->security->getToken();
    if (null !== $token) {
        $decodedToken = $this->tokenManager->decode($token);
        $expire = $decodedToken['exp'] ?? 0;
        $tokenExpired = (new \DateTime())->setTimestamp($expire) < new \DateTime();
    }

    $newClientMeta = [
        'host' => $host,
        'userAgent' => $userAgent,
        'ip' => $ip,
        'tokenExpired' => $tokenExpired,
    ];

    // Diff-aware write: only flush if anything actually differs from
    // the current entity state.  For a stable screen between deploys
    // this means zero DB writes.
    $hasChanges =
        $screenUser->getReleaseVersion() !== $releaseVersion
        || $screenUser->getReleaseTimestamp() !== (int) $releaseTimestamp
        || $screenUser->getClientMeta() !== $newClientMeta;

    if ($hasChanges) {
        $screenUser->setReleaseVersion($releaseVersion);
        $screenUser->setReleaseTimestamp((int) $releaseTimestamp);
        $screenUser->setClientMeta($newClientMeta);
        $screenUser->setLatestRequest(new \DateTime());

        $this->entityManager->flush();
        $this->entityManager->detach($screenUser);  // Step 1's fix
    }

    // Return value is unused (cache is write-only for throttling).
    return ['throttled' => true];
}
```

Note the change in semantics for `ScreenUser::latestRequest` in the DB:
it now means "time of last metadata change" rather than "time of last
request."  That's correct:

- Real-time "last seen" is served from Redis (Step 17b)
- The DB field becomes "when this screen last booted / deployed / moved"
- If Redis is down, the fallback is stale but still useful
  ("this screen was last seen in its current state at X")

Update the admin UI documentation to clarify the two timestamps'
meanings.

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
```

**Changelog:** Changed: TRACK_SCREEN_INFO DB writes now only occur when heavy metadata (releaseVersion, clientMeta, IP, user-agent) actually differs from the stored value. Stable screens between deploys produce zero DB writes from tracking. Reduces write volume by ~99% for typical deployments. DB `latestRequest` field now reflects "last metadata change" rather than "last request" — real-time last-seen served from Redis (Step 17b).

---

### Step 17e — Screen uptime tracking via boot session

**Branch:** `feat/screen-uptime-tracking`

**Depends on:** Step 17a, Step 17d.

**Motivation:** Fleet operators repeatedly ask the same diagnostic
question: "is this screen stable, or has it been rebooting?" Two
kiosks that both pinged the API 30 seconds ago look identical from
`latestRequest` alone — but one has been running for 3 weeks and the
other has reloaded 40 times in the last hour. That distinction matters
for triage: the first is healthy, the second needs investigation.

Uptime is the natural metric. Formally: how long has the current page
lifecycle been running? A reload (any cause — user, SW watchdog,
ErrorBoundary, release redirect, crash-guard, watchdog navigate)
resets it to zero.

The client knows exactly when it booted (the moment `index.jsx` runs).
Sending that to the backend gives operators a uptime number they can
glance at. But a literal timestamp has a clock-skew problem: if the
kiosk's system clock is wrong (common — kiosk hardware often lacks
reliable NTP), the computed uptime would be wrong too.

The fix is a **boot session ID** — a random UUID generated once per
page load, sent on every request. The backend doesn't care about the
client's wall-clock time; it cares whether the session ID matches the
previous one:

- Session ID unchanged from last tracked request → same boot → no change
- Session ID differs → client has rebooted → record `bootTimestamp = now()`
  using the server's authoritative clock

Uptime on read is simply `now - bootTimestamp`, computed with the
server's clock end-to-end. Client clock drift is irrelevant.

The check plugs directly into Step 17d's diff-aware logic: the boot
session ID comparison becomes one more field in the `$hasChanges`
evaluation. No separate write path, no additional DB query for
freshness checks. And like Step 17d, the DB write only fires when the
session ID actually changes — at most once per page lifecycle.

**Schema:**

Two nullable columns on `screen_user`:

- `boot_session_id VARCHAR(36) NULL` — UUID from the most recent boot
- `boot_timestamp DATETIME NULL` — server time when that session was
  first observed

Nullable so existing rows (for screens that haven't made a request
since this migration ran) don't need backfilling.  The next tracked
request from each screen populates them naturally.

**RED — write tests:**

File: `tests/EventSubscriber/ScreenUptimeTrackingTest.php`

1. Send a tracked request with a new `X-OS2Display-Client-Boot-Session`
   value.  Assert `ScreenUser.bootSessionId` is set to the value and
   `ScreenUser.bootTimestamp` is set to `now()` (within 1s tolerance).
2. Send a second request with the SAME boot session ID (and same
   heavy metadata — nothing else to write).  Assert NO DB UPDATE
   (diff-aware path confirms no change).
3. Send a third request with a DIFFERENT boot session ID.  Assert
   `bootTimestamp` is updated to the new `now()` (reboot detected).
4. Request with no `X-OS2Display-Client-Boot-Session` header (legacy
   client).  Assert no change to bootSessionId / bootTimestamp —
   the existing values remain untouched.

File: `tests/State/ScreenProviderUptimeTest.php`

1. Populate ScreenUser with `bootTimestamp` set to 2 hours ago.  Call
   `GET /v2/screens/{ulid}`.  Assert the response's
   `status.uptime.seconds` is ~7200 (within a reasonable tolerance).
2. ScreenUser with null `bootTimestamp` (never seen since migration).
   Assert `status.uptime` is null or absent, not an error.
3. `TRACK_SCREEN_INFO=false`.  Assert `status` is absent entirely
   (existing behavior preserved).

File: `assets/client/__tests__/boot-session.test.jsx`

1. Import the boot session module twice; assert it returns the same
   ID both times (initialized once, singleton).
2. After a simulated page reload (fresh module registry), assert a
   different ID is returned.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenUptime
docker compose run --rm node npx vitest run
```

**GREEN — implement:**

**Step A — Schema migration:**

Create `migrations/VersionYYYYMMDDHHMMSS.php`:

```php
public function up(Schema $schema): void
{
    $this->addSql(
        'ALTER TABLE screen_user '
        .'ADD boot_session_id VARCHAR(36) DEFAULT NULL, '
        .'ADD boot_timestamp DATETIME DEFAULT NULL '
        ."COMMENT '(DC2Type:datetime)'"
    );
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE screen_user DROP boot_session_id, DROP boot_timestamp');
}
```

**Step B — Entity fields:**

In `src/Entity/ScreenUser.php`:

```php
#[ORM\Column(type: 'string', length: 36, nullable: true)]
private ?string $bootSessionId = null;

#[ORM\Column(type: 'datetime', nullable: true)]
private ?\DateTime $bootTimestamp = null;

public function getBootSessionId(): ?string { return $this->bootSessionId; }
public function setBootSessionId(?string $id): void { $this->bootSessionId = $id; }

public function getBootTimestamp(): ?\DateTime { return $this->bootTimestamp; }
public function setBootTimestamp(?\DateTime $ts): void { $this->bootTimestamp = $ts; }
```

**Step C — Client generates session ID:**

Create `assets/client/util/boot-session.js`:

```js
/**
 * A random identifier generated once per page lifecycle.
 * Survives across component remounts, resets on every page reload.
 * The backend uses this to detect when a screen has rebooted
 * (session ID change) and to compute uptime.
 */
const BOOT_SESSION_ID = typeof crypto !== "undefined" && crypto.randomUUID
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

export default BOOT_SESSION_ID;
```

The `crypto.randomUUID()` check + fallback handles older browsers
without the WebCrypto API.  The fallback isn't cryptographically
secure but doesn't need to be — collisions would require the same
kiosk hardware to boot twice within the same millisecond AND produce
the same random suffix.

**Step D — SW attaches header:**

In `assets/client/sw/index.js`, the main thread passes the session ID
at registration:

```js
// assets/client/service/sw-registration.js
import bootSessionId from "../util/boot-session.js";

await navigator.serviceWorker.register("/client/sw.js", {
    updateViaCache: "none",
});

// Post the boot session to the SW so it can attach it to requests.
navigator.serviceWorker.controller?.postMessage({
    type: "setBootSession",
    bootSessionId,
});
```

SW stores and attaches:

```js
// assets/client/sw/fetch.js
let bootSessionId = null;

self.addEventListener("message", (event) => {
    if (event.data?.type === "setBootSession") {
        bootSessionId = event.data.bootSessionId;
    }
});

function injectHeaders(request, token) {
    const headers = new Headers(request.headers);
    if (token) headers.set("Authorization", `Bearer ${token}`);
    headers.set("X-OS2Display-Client-Release-Version", CLIENT_RELEASE_VERSION);
    headers.set("X-OS2Display-Client-Release-Timestamp", CLIENT_RELEASE_TIMESTAMP);
    if (bootSessionId) {
        headers.set("X-OS2Display-Client-Boot-Session", bootSessionId);
    }
    return new Request(request, { headers });
}
```

**Step E — Subscriber tracks session change (extends Step 17d):**

Extend `ScreenUserRequestSubscriber::createCacheEntry` from Step 17d:

```php
$bootSessionId = $request->headers->get('X-OS2Display-Client-Boot-Session');

// ... extract other metadata as in Step 17d ...

$hasChanges =
    $screenUser->getReleaseVersion() !== $releaseVersion
    || $screenUser->getReleaseTimestamp() !== (int) $releaseTimestamp
    || $screenUser->getClientMeta() !== $newClientMeta
    || ($bootSessionId !== null
        && $screenUser->getBootSessionId() !== $bootSessionId);

if ($hasChanges) {
    $screenUser->setReleaseVersion($releaseVersion);
    $screenUser->setReleaseTimestamp((int) $releaseTimestamp);
    $screenUser->setClientMeta($newClientMeta);
    $screenUser->setLatestRequest(new \DateTime());

    // Boot session change → record new boot time using server clock.
    if ($bootSessionId !== null
        && $screenUser->getBootSessionId() !== $bootSessionId) {
        $screenUser->setBootSessionId($bootSessionId);
        $screenUser->setBootTimestamp(new \DateTime());
    }

    $this->entityManager->flush();
    $this->entityManager->detach($screenUser);
}
```

Note the `$bootSessionId !== null` guard — a legacy client (pre-17e)
without the header doesn't trigger a session change detection. This
preserves backward compatibility during the rollout window.

**Step F — ScreenProvider exposes uptime:**

In `src/State/ScreenProvider.php`, extend the status payload with
computed uptime:

```php
if ($this->trackScreenInfo) {
    $screenUser = $object->getScreenUser();
    $status = null;

    if (null != $screenUser) {
        $status = $this->getStatus($screenUser);

        // Real-time last-seen from Redis (Step 17b).
        $screenUserId = $screenUser->getId()?->jsonSerialize();
        if (null !== $screenUserId) {
            $liveItem = $this->screenStatusCache->getItem("last-seen.$screenUserId");
            if ($liveItem->isHit()) {
                $status['latestRequestDateTime'] = $liveItem->get();
            }
        }

        // Uptime: time since last observed boot session change.
        $bootTimestamp = $screenUser->getBootTimestamp();
        if (null !== $bootTimestamp) {
            $uptimeSeconds = (new \DateTime())->getTimestamp() - $bootTimestamp->getTimestamp();
            $status['uptime'] = [
                'bootTimestamp' => $bootTimestamp->format('c'),
                'seconds' => max(0, $uptimeSeconds),
            ];
        }
    }

    $output->status = $status;
}
```

**Step G — CORS allowlist:**

Add to `config/packages/nelmio_cors.yaml` `allow_headers`:

```yaml
allow_headers:
    # ... existing headers ...
    - 'X-OS2Display-Client-Boot-Session'
```

**Verify:**
```bash
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Screen uptime tracking. Client sends a per-boot session UUID in the `X-OS2Display-Client-Boot-Session` header; backend records server-side boot timestamp when the session ID changes. `GET /v2/screens/{id}.status` now includes `uptime.seconds` and `uptime.bootTimestamp`. Uses server clock end-to-end — no dependency on client clock accuracy. Integrates with Step 17d's diff-aware write path: a session ID change is a metadata change, so a reboot triggers the one DB write needed to capture it; subsequent requests in the same session produce no writes.

---

### Step 17f — Client vitals telemetry

**Branch:** `feat/client-vitals`

**Depends on:** Step 15, Step 17a, Step 17b.

**Motivation:** Even with Step 17e's uptime metric, the admin view
still can't distinguish "kiosk hardware is struggling" from "kiosk is
fine." A screen can have days of uptime and great last-seen freshness
while simultaneously dropping frames, leaking memory, and throttled on
2G network. Operators need to see those failure modes before they
become reboots.

Five browser-maintained metrics cover the diagnostic surface for
kiosks, each answering a distinct question:

| Metric | Source | Detects |
|---|---|---|
| `heap` | `performance.memory.usedJSHeapSize` | Memory leak (growth over time) |
| `longTasks` | `PerformanceObserver('longtask')` | Main-thread blocking from heavy templates |
| `fps` | `requestAnimationFrame` sampling | Render degradation |
| `storage` | `navigator.storage.estimate()` | Cache/IDB bloat |
| `net` | `navigator.connection` | Intermittent connectivity |

All five are already maintained by the browser — reading them costs a
few microseconds. The rAF FPS sampler runs continuously but is just
one counter per frame. No COOP/COEP changes needed: we use
`performance.memory` (Chrome's non-standard API) rather than
`measureUserAgentSpecificMemory`. On browsers without
`performance.memory` (Firefox, Safari), the `heap` field is simply
absent from the payload — every vital is independently guarded.

Vitals are volatile by nature — they change on every sample. If
routed through Step 17d's diff-aware write path, every request
would trigger a DB write and defeat the whole optimization.
The right architecture is to mirror Step 17b's Redis pattern: vitals
live in a per-screen Redis key, written on every receipt, read by
`ScreenProvider` when the admin API is called. Zero DB writes from
vitals ever.

Sampling rhythm: compute once every 30s in the main thread (matches
the heartbeat cadence roughly), post to SW, SW attaches the latest
value to every outgoing `/v2/` request.  Backend updates the Redis
key on every receipt.  Admin API reads the latest from Redis on
demand.

**Payload shape:**

```json
{
  "sampledAt": "2026-04-17T10:23:45Z",
  "heap": 45000000,
  "longTasks": { "count": 3, "totalMs": 240 },
  "fps": 58,
  "storage": 15_000_000,
  "net": { "type": "4g", "rtt": 50 }
}
```

Size: ~150 bytes stringified. Fits comfortably in a single HTTP header.

**RED — write tests:**

File: `assets/client/util/__tests__/vitals.test.js`

1. Mock `performance.memory` with `{ usedJSHeapSize: 50_000_000 }`.
   Call `collectVitals()`.  Assert `result.heap === 50_000_000`.
2. Browser without `performance.memory` (unset in mock).  Call
   `collectVitals()`.  Assert `result.heap` is absent (not null,
   not 0 — absent).
3. Simulate `longtask` `PerformanceObserver` entries.  Call
   `collectVitals()`.  Assert `result.longTasks.count` and
   `totalMs` match; call again, assert counters reset (each sample
   reports since-last-sample, not cumulative).
4. Browser without `navigator.storage.estimate`.  Assert `storage`
   is absent.
5. Browser without `navigator.connection`.  Assert `net` is absent.
6. All APIs missing.  Assert `collectVitals()` returns
   `{ sampledAt: ... }` — always includes the timestamp, other
   fields absent.

File: `tests/EventSubscriber/ScreenVitalsHeaderTest.php`

1. Send request with `X-OS2Display-Client-Vitals: {json}` header.
   Assert the Redis key `vitals.{ulid}` is set to the JSON string.
2. Send request without the header.  Assert no Redis write for
   vitals (existing key unchanged if present).
3. Send two requests in sequence with different vitals payloads.
   Assert the Redis value reflects the most recent payload.

File: `tests/State/ScreenProviderVitalsTest.php`

1. Populate `vitals.{ulid}` in Redis with a known payload.  Call
   `GET /v2/screens/{ulid}`.  Assert response `status.vitals` is
   the parsed JSON.
2. No Redis key present.  Assert `status.vitals` is absent from
   the response (not null — absent).
3. `TRACK_SCREEN_INFO=false`.  Assert `status` is absent entirely.

```bash
docker compose run --rm node npx vitest run
docker compose run --rm phpfpm composer test -- --filter=ScreenVitals
```

**GREEN — implement:**

**Step A — Vitals collector module:**

Create `assets/client/util/vitals.js`:

```js
// Module-scope long-task counters, drained on each sample.
let longTaskCount = 0;
let longTaskTotalMs = 0;

if (typeof PerformanceObserver !== "undefined") {
  try {
    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        longTaskCount++;
        longTaskTotalMs += entry.duration;
      }
    });
    observer.observe({ entryTypes: ["longtask"] });
  } catch {
    // longtask entry type not supported — skip silently.
  }
}

// rAF FPS sampler — maintains a 10-second rolling window of frame times.
let frameTimes = [];
function sampleFrame(ts) {
  frameTimes.push(ts);
  const cutoff = ts - 10_000;
  frameTimes = frameTimes.filter((t) => t > cutoff);
  requestAnimationFrame(sampleFrame);
}
if (typeof requestAnimationFrame !== "undefined") {
  requestAnimationFrame(sampleFrame);
}

function currentFps() {
  if (frameTimes.length < 2) return null;
  const span = frameTimes[frameTimes.length - 1] - frameTimes[0];
  if (span <= 0) return null;
  return Math.round(((frameTimes.length - 1) / span) * 1000);
}

export async function collectVitals() {
  const vitals = { sampledAt: new Date().toISOString() };

  // Heap (Chrome non-standard).
  if (typeof performance !== "undefined" && performance.memory) {
    vitals.heap = performance.memory.usedJSHeapSize;
  }

  // Long tasks (drain counters so each sample is "since last sample").
  if (longTaskCount > 0) {
    vitals.longTasks = {
      count: longTaskCount,
      totalMs: Math.round(longTaskTotalMs),
    };
    longTaskCount = 0;
    longTaskTotalMs = 0;
  }

  // Effective FPS.
  const fps = currentFps();
  if (fps !== null) vitals.fps = fps;

  // Storage usage.
  if (navigator.storage?.estimate) {
    try {
      const est = await navigator.storage.estimate();
      if (est.usage !== undefined) vitals.storage = est.usage;
    } catch {
      // Ignore — storage quota API refused.
    }
  }

  // Network info.
  if (navigator.connection) {
    vitals.net = {
      type: navigator.connection.effectiveType,
      rtt: navigator.connection.rtt,
    };
  }

  return vitals;
}
```

**Step B — Main thread posts vitals to SW periodically:**

In `assets/client/app.jsx` (or the boot sequence near SW registration):

```js
import { collectVitals } from "./util/vitals";

// After SW registration and after config load:
const VITALS_INTERVAL = 30_000;  // Fixed — not tied to errorRecoveryTimeout.

setInterval(async () => {
  const vitals = await collectVitals();
  navigator.serviceWorker.controller?.postMessage({
    type: "setVitals",
    vitals: JSON.stringify(vitals),
  });
}, VITALS_INTERVAL);
```

The `setInterval` is a module-scope timer.  Cleared on page unload
automatically; no explicit cleanup needed for the kiosk's
never-navigate lifecycle.

**Step C — SW stores and attaches the header:**

In `assets/client/sw/fetch.js`:

```js
let vitalsPayload = null;

// In the SW's message handler:
self.addEventListener("message", (event) => {
  if (event.data?.type === "setVitals") {
    vitalsPayload = event.data.vitals;
  }
  // ... other message types ...
});

// Extend injectHeaders:
function injectHeaders(request, token) {
  const headers = new Headers(request.headers);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  headers.set("X-OS2Display-Client-Release-Version", CLIENT_RELEASE_VERSION);
  headers.set("X-OS2Display-Client-Release-Timestamp", CLIENT_RELEASE_TIMESTAMP);
  if (bootSessionId) {
    headers.set("X-OS2Display-Client-Boot-Session", bootSessionId);
  }
  if (vitalsPayload) {
    headers.set("X-OS2Display-Client-Vitals", vitalsPayload);
  }
  return new Request(request, { headers });
}
```

**Step D — Subscriber writes to Redis:**

Extend `ScreenUserRequestSubscriber::onKernelRequest`:

```php
$vitalsJson = $request->headers->get('X-OS2Display-Client-Vitals');
if (null !== $vitalsJson) {
    $vitalsItem = $this->screenStatusCache->getItem("vitals.$key");
    $vitalsItem->set($vitalsJson);
    $vitalsItem->expiresAfter($this->trackScreenInfoUpdateIntervalSeconds * 4);
    $this->screenStatusCache->save($vitalsItem);
}
```

Note: this is outside the existing `->get($key, $callback)` cache
contract — vitals update on every request, not on a throttled
schedule. The main throttled path (DB write for heavy metadata) is
unaffected.  No DB write happens from vitals ever.

**Step E — ScreenProvider exposes vitals:**

In `src/State/ScreenProvider.php`, alongside the uptime lookup:

```php
$vitalsItem = $this->screenStatusCache->getItem("vitals.$screenUserId");
if ($vitalsItem->isHit()) {
    $status['vitals'] = json_decode($vitalsItem->get(), true);
}
```

**Step F — CORS allowlist:**

Add to `config/packages/nelmio_cors.yaml` `allow_headers`:

```yaml
allow_headers:
    # ... existing headers ...
    - 'X-OS2Display-Client-Vitals'
```

**Verify:**
```bash
docker compose run --rm node npm run build
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Client vitals telemetry. Main thread samples heap size, long-task count/duration, effective FPS, storage usage, and network info every 30s; SW attaches the latest sample to every `/v2/` request as `X-OS2Display-Client-Vitals` header. Backend stores in Redis (no DB writes). `GET /v2/screens/{id}.status.vitals` exposes the current sample. Browser APIs independently guarded — missing fields simply absent rather than causing errors. No COOP/COEP changes required.

---

### Step 17g — Boot environment capture

**Branch:** `feat/boot-environment`

**Depends on:** Step 17a, Step 17d, Step 17e.

**Motivation:** When a kiosk misbehaves, the first questions an
operator asks are rarely about runtime state — they're about the
environment the client is actually running in. "Is the browser
actually fullscreen?" (`viewport` vs `screen`). "Is this really a
4K display or a 1080p rendering to a 4K panel?" (`screen.width` +
`devicePixelRatio`). "Is the timezone set correctly?"
(`Intl.resolvedOptions().timeZone`). "Is this the weak hardware we
have on the backorder list or the new gen?" (`hardwareConcurrency`,
`deviceMemory`).

None of this data changes during a session — it's determined at boot
and remains constant until reboot. That's different from Step 17f's
vitals (continuously fluctuating runtime state) and different from
Step 17b's last-seen (timestamp of last observed activity). Boot
environment is pure static profiling: "what kind of thing is this?"

Step 17e already detects the moment of interest — when the boot
session ID changes, the screen just rebooted and a fresh environment
is worth capturing. The subscriber can read an `X-OS2Display-Client-Boot-Environment`
header on the same request that carries the new session ID, store it
alongside `bootTimestamp`, and expose it via `GET /v2/screens/{id}`
under `status.environment`.

Since this data is static per boot, it belongs in the DB (unlike vitals
which belong in Redis). It joins `boot_session_id` and `boot_timestamp`
as boot-scoped fields — they always change together when the session
changes, and never change otherwise.

**Captured fields:**

| Field | Source | Example | Why |
|---|---|---|---|
| `screen.width` × `.height` | `window.screen.width/height` | `3840 × 2160` | Display physical resolution |
| `pixelRatio` | `window.devicePixelRatio` | `1` or `2` | DPI scaling — reveals upscaling |
| `viewport.width` × `.height` | `window.innerWidth/Height` | `3840 × 2160` | If ≠ screen, browser isn't fullscreen |
| `cores` | `navigator.hardwareConcurrency` | `4` | Hardware capacity |
| `memory` | `navigator.deviceMemory` | `2` (GB) | Rough RAM size |
| `timezone` | `Intl.DateTimeFormat().resolvedOptions().timeZone` | `Europe/Copenhagen` | Schedule correctness |
| `language` | `navigator.language` | `da-DK` | Locale verification |

Total payload: ~200 bytes JSON. Sent as a request header on every
`/v2/` call (the SW adds it once at activation time, then attaches it
unchanged forever until the page reloads). Backend writes the DB only
when the boot session also changed — so at most one write per kiosk
boot captures the environment.

**Schema:**

One additional nullable column on `screen_user`:

- `boot_environment JSON DEFAULT NULL` — parsed and returned as-is in
  the admin API. Using JSON rather than discrete columns because the
  shape might evolve over time without migration churn.

**RED — write tests:**

File: `assets/client/util/__tests__/boot-environment.test.js`

1. Call `collectBootEnvironment()` in a jsdom environment with known
   screen/viewport/navigator mocks. Assert all fields are present
   with the mocked values.
2. `navigator.deviceMemory` absent (Safari). Assert `memory` field is
   absent from the result, other fields present.
3. `navigator.hardwareConcurrency` absent. Assert `cores` is absent.
4. Call twice. Assert the same object is returned (memoized — not
   re-computed on every call).

File: `tests/EventSubscriber/ScreenBootEnvironmentTest.php`

1. Send request with a new boot session ID AND an
   `X-OS2Display-Client-Boot-Environment` header. Assert
   `ScreenUser.bootEnvironment` is populated with the parsed JSON.
2. Send a subsequent request with the SAME boot session ID but a
   DIFFERENT environment header (shouldn't happen in practice, but
   defensive). Assert `bootEnvironment` is NOT overwritten —
   environment only updates on session change.
3. Send a request with a new boot session ID but NO environment
   header (legacy client). Assert `bootSessionId` updates but
   `bootEnvironment` is left untouched.
4. Malformed JSON in the header. Assert the subscriber logs a
   warning and skips the environment update; the session ID still
   updates correctly.

File: `tests/State/ScreenProviderBootEnvironmentTest.php`

1. Populate `ScreenUser.bootEnvironment` with a known JSON structure.
   Call `GET /v2/screens/{ulid}`. Assert response
   `status.environment` is the parsed JSON object.
2. Null `bootEnvironment`. Assert `status.environment` is absent
   from the response.

```bash
docker compose run --rm node npx vitest run
docker compose run --rm phpfpm composer test -- --filter=ScreenBootEnvironment
```

**GREEN — implement:**

**Step A — Schema migration:**

```php
public function up(Schema $schema): void
{
    $this->addSql(
        'ALTER TABLE screen_user '
        ."ADD boot_environment JSON DEFAULT NULL COMMENT '(DC2Type:json)'"
    );
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE screen_user DROP boot_environment');
}
```

**Step B — Entity field:**

In `src/Entity/ScreenUser.php`:

```php
#[ORM\Column(type: Types::JSON, nullable: true)]
private ?array $bootEnvironment = null;

public function getBootEnvironment(): ?array { return $this->bootEnvironment; }
public function setBootEnvironment(?array $env): void { $this->bootEnvironment = $env; }
```

**Step C — Client collector (memoized):**

Create `assets/client/util/boot-environment.js`:

```js
/**
 * Capture static properties of the environment the client is booting into.
 *
 * Computed once per module load (i.e., once per boot) and memoized.
 * Every field is independently guarded so missing browser APIs produce
 * an absent field rather than an error.
 */
let cached = null;

export default function collectBootEnvironment() {
  if (cached !== null) return cached;

  const env = {};

  if (typeof window !== "undefined") {
    if (window.screen) {
      env.screen = { width: window.screen.width, height: window.screen.height };
    }
    if (typeof window.devicePixelRatio === "number") {
      env.pixelRatio = window.devicePixelRatio;
    }
    env.viewport = { width: window.innerWidth, height: window.innerHeight };
  }

  if (typeof navigator !== "undefined") {
    if (typeof navigator.hardwareConcurrency === "number") {
      env.cores = navigator.hardwareConcurrency;
    }
    if (typeof navigator.deviceMemory === "number") {
      env.memory = navigator.deviceMemory;
    }
    if (navigator.language) {
      env.language = navigator.language;
    }
  }

  try {
    env.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch {
    // Intl not available — skip.
  }

  cached = env;
  return env;
}
```

**Step D — Pass to SW at registration:**

In `assets/client/service/sw-registration.js`:

```js
import bootSessionId from "../util/boot-session.js";
import collectBootEnvironment from "../util/boot-environment.js";

await navigator.serviceWorker.register("/client/sw.js", {
  updateViaCache: "none",
});

navigator.serviceWorker.controller?.postMessage({
  type: "setBootSession",
  bootSessionId,
  bootEnvironment: JSON.stringify(collectBootEnvironment()),
});
```

**Step E — SW attaches header:**

In `assets/client/sw/fetch.js`, extend the state:

```js
let bootSessionId = null;
let bootEnvironment = null;

self.addEventListener("message", (event) => {
  if (event.data?.type === "setBootSession") {
    bootSessionId = event.data.bootSessionId;
    bootEnvironment = event.data.bootEnvironment ?? null;
  }
  // ... other handlers ...
});

function injectHeaders(request, token) {
  const headers = new Headers(request.headers);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  headers.set("X-OS2Display-Client-Release-Version", CLIENT_RELEASE_VERSION);
  headers.set("X-OS2Display-Client-Release-Timestamp", CLIENT_RELEASE_TIMESTAMP);
  if (bootSessionId) {
    headers.set("X-OS2Display-Client-Boot-Session", bootSessionId);
  }
  if (bootEnvironment) {
    headers.set("X-OS2Display-Client-Boot-Environment", bootEnvironment);
  }
  if (vitalsPayload) {
    headers.set("X-OS2Display-Client-Vitals", vitalsPayload);
  }
  return new Request(request, { headers });
}
```

**Step F — Subscriber captures on session change:**

Extend `ScreenUserRequestSubscriber::createCacheEntry` from Step 17e:

```php
if ($bootSessionId !== null
    && $screenUser->getBootSessionId() !== $bootSessionId) {
    $screenUser->setBootSessionId($bootSessionId);
    $screenUser->setBootTimestamp(new \DateTime());

    // Capture the environment the new boot is running in.
    $envJson = $request->headers->get('X-OS2Display-Client-Boot-Environment');
    if (null !== $envJson) {
        try {
            $env = json_decode($envJson, true, 5, JSON_THROW_ON_ERROR);
            if (is_array($env)) {
                $screenUser->setBootEnvironment($env);
            }
        } catch (\JsonException $e) {
            // Malformed header — log and skip, don't fail the request.
            // Session update still proceeds.
        }
    }
}
```

The environment update is nested inside the session-change branch —
environment never updates without a session change. A kiosk whose
screen was resized mid-session (rare but possible) won't trigger a
write; the next reboot will capture the new dimensions.

**Step G — ScreenProvider exposes the field:**

In `src/State/ScreenProvider.php`, alongside uptime and vitals:

```php
$env = $screenUser->getBootEnvironment();
if (null !== $env) {
    $status['environment'] = $env;
}
```

**Step H — CORS allowlist:**

Add to `config/packages/nelmio_cors.yaml` `allow_headers`:

```yaml
allow_headers:
    # ... existing headers ...
    - 'X-OS2Display-Client-Boot-Environment'
```

**Verify:**
```bash
docker compose run --rm node npm run build
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Added: Boot environment capture. Client collects static display/device info (screen resolution, DPI, viewport, CPU cores, RAM, timezone, language) on boot and sends it as `X-OS2Display-Client-Boot-Environment` header. Backend captures into a new nullable JSON column `screen_user.boot_environment` only when the boot session ID changes — typically one DB write per reboot. Surfaced via `GET /v2/screens/{id}.status.environment`. Helps operators distinguish hardware generations, verify fullscreen operation, and confirm timezone correctness.

---

### Step 17h — Clock skew tracking

**Branch:** `feat/clock-skew-tracking`

**Depends on:** Step 17a, Step 17b.

**Motivation:** Kiosk hardware is notorious for bad clocks. Cheap
boards skip the RTC battery; NTP configuration is inconsistent across
deployments; firmware bugs cause time zone tables to be out of date.
The symptom on OS2Display is silent: a screen with a 5-minute clock
skew still shows content, still refreshes, still authenticates — but
its 9:00 AM schedule runs at 8:55. Ops teams discover the skew by
chance, usually after a stakeholder complains that "the cafeteria menu
showed tomorrow's lunch during today's service."

The fix isn't to synchronize the kiosk's clock (we can't; that's an OS-level
concern). The fix is to **surface** the skew so operators can see it and
investigate at the OS/NTP layer.

Measurement is trivial: client sends its current time in a header on
every `/v2/` request; backend computes `skew = clientTime - serverTime`
on receipt. The server's clock is the authority — we care about whether
the kiosk matches the server, not absolute correctness. One-way network
latency adds noise (request-travel time means the client's timestamp
is always slightly in the past by the time the server reads it) but
that's sub-second on any functioning network. For kiosk skew (measured
in tens of seconds to minutes), the noise is irrelevant.

Redis-backed like vitals — skew changes continuously as clocks drift
independently, and admins only need the most recent value. No DB writes.

The admin API exposes raw seconds; the admin UI decides how to render
severity (green under 10s, yellow under 60s, red beyond). Keeping the
threshold on the UI side avoids another env var and lets deployments
customize rendering without backend changes.

**RED — write tests:**

File: `tests/EventSubscriber/ScreenClockSkewTest.php`

1. Send a request with `X-OS2Display-Client-Time` set to `now + 45s`.
   Mock server time accordingly. Assert the Redis key
   `clock-skew.{ulid}` is set with seconds value `45`.
2. Send a request with client time set to `now - 120s`. Assert
   stored value is `-120`.
3. Send a request without the header. Assert no Redis key write
   (existing value preserved if any).
4. Send a request with a malformed time string. Assert the
   subscriber logs a warning and skips the skew update — doesn't
   throw, doesn't break the request flow.
5. Send multiple requests in sequence with varying skew values.
   Assert the Redis value reflects the most recent measurement.

File: `tests/State/ScreenProviderClockSkewTest.php`

1. Populate `clock-skew.{ulid}` with `{ "seconds": 45, "sampledAt": "..." }`.
   Call `GET /v2/screens/{ulid}`. Assert response
   `status.clockSkew` matches.
2. No Redis key present. Assert `status.clockSkew` is absent
   from the response (not null — absent).
3. `TRACK_SCREEN_INFO=false`. Assert `status` is absent entirely.

```bash
docker compose run --rm phpfpm composer test -- --filter=ScreenClockSkew
```

**GREEN — implement:**

**Step A — SW attaches the client time header:**

In `assets/client/sw/fetch.js`, extend `injectHeaders`:

```js
function injectHeaders(request, token) {
  const headers = new Headers(request.headers);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  headers.set("X-OS2Display-Client-Release-Version", CLIENT_RELEASE_VERSION);
  headers.set("X-OS2Display-Client-Release-Timestamp", CLIENT_RELEASE_TIMESTAMP);
  headers.set("X-OS2Display-Client-Time", new Date().toISOString());
  if (bootSessionId) {
    headers.set("X-OS2Display-Client-Boot-Session", bootSessionId);
  }
  if (bootEnvironment) {
    headers.set("X-OS2Display-Client-Boot-Environment", bootEnvironment);
  }
  if (vitalsPayload) {
    headers.set("X-OS2Display-Client-Vitals", vitalsPayload);
  }
  return new Request(request, { headers });
}
```

Generated at header-injection time, not request-construction time,
so the timestamp reflects when the request actually goes out over
the wire rather than when the main thread initiated the fetch.

**Step B — Subscriber computes and stores skew:**

Extend `ScreenUserRequestSubscriber::onKernelRequest` (alongside the
vitals Redis write from Step 17f):

```php
$clientTimeHeader = $request->headers->get('X-OS2Display-Client-Time');
if (null !== $clientTimeHeader) {
    try {
        $clientTime = new \DateTimeImmutable($clientTimeHeader);
        $serverTime = new \DateTimeImmutable();
        $skewSeconds = $clientTime->getTimestamp() - $serverTime->getTimestamp();

        $skewItem = $this->screenStatusCache->getItem("clock-skew.$key");
        $skewItem->set(json_encode([
            'seconds' => $skewSeconds,
            'sampledAt' => $serverTime->format('c'),
        ]));
        $skewItem->expiresAfter($this->trackScreenInfoUpdateIntervalSeconds * 4);
        $this->screenStatusCache->save($skewItem);
    } catch (\Exception $e) {
        // Malformed client time — log at debug, skip the measurement.
        // Don't fail the request.
    }
}
```

The server time is captured at message-receipt (before any PHP work),
giving a consistent reference point. Sub-second precision isn't
meaningful — kiosk skew is measured in seconds, not milliseconds.

**Step C — ScreenProvider surfaces the measurement:**

In `src/State/ScreenProvider.php`, alongside the vitals lookup:

```php
$skewItem = $this->screenStatusCache->getItem("clock-skew.$screenUserId");
if ($skewItem->isHit()) {
    $status['clockSkew'] = json_decode($skewItem->get(), true);
}
```

The payload structure is fixed from Step B — `{ seconds, sampledAt }`
— so no shape transformation needed here.

**Step D — CORS allowlist:**

Add to `config/packages/nelmio_cors.yaml` `allow_headers`:

```yaml
allow_headers:
    # ... existing headers ...
    - 'X-OS2Display-Client-Time'
```

**Verify:**
```bash
docker compose run --rm node npm run build
docker compose run --rm phpfpm composer test
docker compose run --rm phpfpm composer code-analysis
docker compose run --rm phpfpm composer coding-standards-check
docker compose run --rm node npx vitest run
```

**Changelog:** Added: Client/server clock skew tracking. SW attaches current client time as `X-OS2Display-Client-Time` to every `/v2/` request; backend computes `skew = client - server` and stores in Redis. `GET /v2/screens/{id}.status.clockSkew` exposes `{ seconds, sampledAt }`. Surfaces cheap-hardware RTC drift and missing NTP setup — the class of silent bugs that cause schedules to fire at the wrong wall time. Admin UI renders severity (raw seconds exposed; threshold decisions made on the UI side).

---

### Step 18 — Update error code taxonomy

**Branch:** `feat/error-code-taxonomy`

**Depends on:** Steps 15, 17.

**Motivation:** The client writes `?status=...&error=...` to the URL via
`statusService.setStatusInUrl()` so operations staff can read the
current state directly from a kiosk's browser URL bar. This is an
invaluable debugging aid — no developer tools, no backend logs, just
look at the screen. The contract is:

```
https://kiosk.example.com/client/?status=running&error=ER105
```

The plan's earlier steps invalidate parts of the current taxonomy and
introduce new failure modes that aren't represented. This step reconciles
the codes with the new architecture AND fixes structural bugs in how
errors are cleared.

**Sticky-error bugs in the current architecture:**

Error clearing today is scattered across individual call sites. Each
site that can successfully complete an operation has to remember to
clear the specific errors its failure would have set. This pattern has
failed in three ways:

1. **ER101 has no clear path outside rebind.** ER101
   (`ERROR_TOKEN_REFRESH_FAILED`) is set in `app.jsx`
   `reauthenticateHandler` and in `tokenService`. The only place that
   clears it is `tokenService.checkLogin()`, which only runs during the
   bind flow. A screen that hits ER101 once, then recovers via a
   successful refresh, carries `?error=ER101` in the URL until rebound.
   Step 6a fixed a separate typo — this is a different bug: the clear
   path simply doesn't exist for routine recovery.

2. **URL params persist across reloads.** `history.replaceState` writes
   `?error=XXX` to the URL, which survives `window.location.reload()`.
   After a reload that successfully boots the app, the URL still carries
   the pre-reload error. An ER202 watchdog-triggered reload that
   successfully recovers shows `?error=ER202` forever. Ops teams waste
   cycles investigating errors that resolved themselves.

3. **No central clearing on STATUS_RUNNING.** When the app reaches
   `STATUS_RUNNING`, by definition every prerequisite (auth, config,
   content fetch) succeeded. Any previous error is, by construction,
   resolved. But `setStatus(STATUS_RUNNING)` doesn't touch the error
   field. Errors that set up a path `init → login → running` can
   linger visible on a working screen.

**Clearing strategy:**

- On boot, read the old `?error=` from the URL into a memory-only
  `previousError` field (for logs/diagnostics), then clear the URL.
  New boot starts with a clean URL — any error that re-fires will
  overwrite.
- `statusService.setStatus()` clears the error on the `STATUS_RUNNING`
  transition. Reaching running is proof of resolution.
- Add `statusService.clearError(code)` as the unambiguous clear API.
  Replaces the scattered `setError(null)` calls. Clear is no-op if the
  current error doesn't match `code` — prevents races where a newer
  error is accidentally cleared by a delayed success handler from an
  older operation.
- ER301 (crash-loop) is the one error that must NOT clear on boot —
  it's meaningful precisely because the screen is cycling. It clears
  explicitly when `crashGuard.reset()` fires on successful boot (the
  `STATUS_RUNNING` transition handler calls `reset()`, which in turn
  clears ER301).

**Dead codes after earlier steps:**

- **ER102** (`ERROR_TOKEN_REFRESH_LOOP_FAILED`) — set inside
  `tokenService.startRefreshing()`. That function is deleted in Step 15.
  The SW now owns the refresh cycle and any failure collapses to ER101.
- **ER104** (`ERROR_RELEASE_FILE_NOT_LOADED`) — set inside
  `releaseService.checkForNewRelease()`. The entire `release-service.js`
  is deleted in Step 17. Release detection is now header-based with no
  failure mode worth surfacing (a missing header on an API response is
  equivalent to "no change" — benign).

**Relocated codes:**

- **ER101** (`ERROR_TOKEN_REFRESH_FAILED`) — currently set by
  `reauthenticateHandler` in `app.jsx` and by `tokenService`. Both
  callers are deleted in Step 15. The SW now detects refresh failure
  and posts `tokenRefreshFailed` to the main thread; the message
  handler sets ER101.
- **ER103** (`ERROR_TOKEN_EXP_IAT_NOT_SET`) — JWT payload validation
  still occurs (now in the SW's `auth.js` module). Set via the same
  message-passing path.
- **ER105, ER106** — same pattern.

**New codes for new failure modes:**

| Code | Constant | When |
|---|---|---|
| ER201 | `ERROR_SW_REGISTRATION_FAILED` | `registerSW()` rejected — browser blocked or SW file missing. Main-thread auth falls back to pre-Step-15 behavior temporarily. |
| ER202 | `ERROR_WATCHDOG_RELOAD` | SW's watchdog navigated the page because heartbeats stopped. Set by the SW via message on activation-after-reload. |
| ER301 | `ERROR_CRASH_LOOP_DETECTED` | Crash-guard is in backoff — recent crashes exceeded threshold, reloads suppressed. Lets ops see "this screen has been cycling" without reading logs. |
| ER302 | `ERROR_UNCAUGHT_EXCEPTION` | `window.onerror` or `unhandledrejection` fired. Set briefly before the subsequent reload. |

**RED — write test:**

File: `assets/client/service/__tests__/status-service-clearing.test.js`

These tests target the structural clearing bugs:

1. Set URL to `?error=ER101` before constructing StatusService.  Construct
   the service.  Assert `statusService.error === null` (URL is cleared)
   and `statusService.previousError === "ER101"` (captured for diagnostics).
2. Set URL to `?error=ER301` (crash-loop) before constructing.  Construct.
   Assert the URL is cleared (new boot shouldn't inherit stale state) —
   but crashGuard.recordCrash() will re-set ER301 if still in backoff.
3. Set `statusService.error = "ER101"`, then call
   `setStatus(STATUS_RUNNING)`.  Assert error is cleared.
4. Set `statusService.error = "ER301"`, then call
   `setStatus(STATUS_RUNNING)`.  Assert error is NOT cleared (ER301 is
   persistent).
5. Set `statusService.error = "ER105"`, call `clearError("ER101")`.
   Assert error is still "ER105" (code mismatch, no-op).
6. Set `statusService.error = "ER105"`, call `clearError("ER105")`.
   Assert error is null.
7. Set `statusService.error = "ER105"`, call `clearError()` with no arg.
   Assert error is null (force clear).

File: `assets/client/util/__tests__/constants.test.js`

1. Assert `constants.ERROR_TOKEN_REFRESH_LOOP_FAILED` is undefined
   (removed).
2. Assert `constants.ERROR_RELEASE_FILE_NOT_LOADED` is undefined
   (removed).
3. Assert `constants.ERROR_SW_REGISTRATION_FAILED` equals `"ER201"`.
4. Assert `constants.ERROR_WATCHDOG_RELOAD` equals `"ER202"`.
5. Assert `constants.ERROR_CRASH_LOOP_DETECTED` equals `"ER301"`.
6. Assert `constants.ERROR_UNCAUGHT_EXCEPTION` equals `"ER302"`.
7. Assert ER101, ER103, ER105, ER106 are unchanged.

File: `assets/client/service/__tests__/sw-registration-error.test.js`

1. Mock `navigator.serviceWorker.register` to reject.
2. Call `registerSW({ onError })`.
3. Assert the caller receives `ERROR_SW_REGISTRATION_FAILED`.
4. Assert `statusService.error === "ER201"` after the callback fires.

File: `assets/client/util/__tests__/crash-guard-error.test.js`

1. Call `recordCrash()` enough times to enter backoff.
2. Assert `statusService.error === "ER301"` was set.
3. Call `reset()`. Assert error is cleared from the URL.

File: `assets/client/util/__tests__/global-error-handlers-error.test.js`

1. Dispatch a `window.onerror` event.
2. Assert `statusService.error === "ER302"` was set before the
   scheduled reload fires.

```bash
docker compose run --rm node npx vitest run
```

**GREEN — implement:**

Refactor `assets/client/service/status-service.js` to own the clearing
strategy:

```js
import constants from '../util/constants';

class StatusService {
  status = constants.STATUS_INIT;
  error = null;

  /**
   * The error that was in the URL when this boot started.
   * Read-only after boot, for logs and diagnostics.
   * Never written back to the URL — keeps the URL reflective of the
   * current boot's state, not a previous life's.
   */
  previousError = null;

  /**
   * Errors that should survive a STATUS_RUNNING transition.
   * ER301 means the screen is actively in a crash loop — clearing it
   * just because we briefly reached running would defeat the whole
   * purpose of the guard.
   */
  static PERSISTENT_ERRORS = new Set([
    constants.ERROR_CRASH_LOOP_DETECTED,
  ]);

  constructor() {
    // On boot: capture the previous URL error for diagnostics, then
    // clear it from the URL so the new boot starts clean.
    const url = new URL(window.location.href);
    const inheritedError = url.searchParams.get("error");
    if (inheritedError) {
      this.previousError = inheritedError;
      url.searchParams.delete("error");
      url.searchParams.delete("status");
      window.history.replaceState(null, "", url);
    }
  }

  setStatus = (newStatus) => {
    this.status = newStatus;

    // Reaching STATUS_RUNNING is proof that all prerequisites resolved.
    // Clear any transient error automatically.
    if (newStatus === constants.STATUS_RUNNING && this.error) {
      if (!StatusService.PERSISTENT_ERRORS.has(this.error)) {
        this.error = null;
      }
    }

    this.setStatusInUrl();
  };

  setError = (newError) => {
    this.error = newError;
    this.setStatusInUrl();
  };

  /**
   * Clear error only if it matches the given code.  Prevents a
   * delayed success handler from clearing a newer, unrelated error.
   * Pass no argument to force-clear regardless.
   */
  clearError = (code = null) => {
    if (code === null || this.error === code) {
      this.error = null;
      this.setStatusInUrl();
    }
  };

  setStatusInUrl = () => {
    const url = new URL(window.location.href);

    if (this.status) {
      url.searchParams.set("status", this.status);
    } else {
      url.searchParams.delete("status");
    }

    if (this.error) {
      url.searchParams.set("error", this.error);
    } else {
      url.searchParams.delete("error");
    }

    window.history.replaceState(null, "", url);
  };
}

const statusService = new StatusService();
export default statusService;
```

Update every call site currently using `setError(null)` to use
`clearError(code)` with the specific code they're clearing. This
prevents the race where a slow success handler from a previous
operation clears a newer error that was set in the meantime.

Update `assets/client/util/constants.js`:

```js
const constants = {
    // ... status values unchanged ...

    // Token / auth errors — still relevant, now set by SW message handlers.
    ERROR_TOKEN_REFRESH_FAILED: "ER101",
    ERROR_TOKEN_EXP_IAT_NOT_SET: "ER103",
    ERROR_TOKEN_EXPIRED: "ER105",
    ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED: "ER106",

    // NOTE: ER102 ERROR_TOKEN_REFRESH_LOOP_FAILED removed — refresh loop
    // replaced by SW in Step 15. Failures now surface as ER101.
    // NOTE: ER104 ERROR_RELEASE_FILE_NOT_LOADED removed — release polling
    // replaced by header-based detection in Step 17.

    // Service worker lifecycle errors.
    ERROR_SW_REGISTRATION_FAILED: "ER201",
    ERROR_WATCHDOG_RELOAD: "ER202",

    // Crash handling errors.
    ERROR_CRASH_LOOP_DETECTED: "ER301",
    ERROR_UNCAUGHT_EXCEPTION: "ER302",

    NO_TOKEN: "NO_TOKEN",
    NO_EXPIRE: "NO_EXPIRE",
    NO_ISSUED_AT: "NO_ISSUED_AT",
};
```

Wire ER201 in `assets/client/service/sw-registration.js`:

```js
import statusService from './status-service';
import constants from '../util/constants';

export async function registerSW(callbacks) {
    try {
        const reg = await navigator.serviceWorker.register(
            "/client/sw.js",
            { updateViaCache: "none" }
        );
        // ... existing setup ...
    } catch (err) {
        logger.error("SW registration failed:", err);
        statusService.setError(constants.ERROR_SW_REGISTRATION_FAILED);
        callbacks?.onError?.(err);
    }
}
```

Wire ER301 in `assets/client/util/crash-guard.js`:

```js
import statusService from '../service/status-service';
import constants from './constants';

const crashGuard = {
    recordCrash() {
        // ... existing logic ...
        if (state.count >= maxCrashes) {
            state.backoffUntil = now + backoffDuration;
            statusService.setError(constants.ERROR_CRASH_LOOP_DETECTED);
        }
        saveState(state);
    },

    reset() {
        try { localStorage.removeItem(STORAGE_KEY); } catch {}
        if (statusService.error === constants.ERROR_CRASH_LOOP_DETECTED) {
            statusService.setError(null);
        }
    },
    // ...
};
```

Wire ER302 in `assets/client/util/global-error-handlers.js`:

```js
function handleFatalError() {
    statusService.setError(constants.ERROR_UNCAUGHT_EXCEPTION);
    crashGuard.recordCrash();

    if (crashGuard.shouldReload()) {
        setTimeout(() => window.location.reload(), reloadDelay);
    }
}
```

Wire ER202 in the SW → main thread message handler (in
`assets/client/service/sw-registration.js`):

```js
navigator.serviceWorker.addEventListener('message', (event) => {
    switch (event.data?.type) {
        case 'watchdogReloadPending':
            // SW is about to call client.navigate() due to missed heartbeats.
            statusService.setError(constants.ERROR_WATCHDOG_RELOAD);
            break;
        case 'tokenRefreshFailed':
            statusService.setError(constants.ERROR_TOKEN_REFRESH_FAILED);
            callbacks.onTokenRefreshFailed?.();
            break;
        // ... other message types
    }
});
```

Note that ER202 is set on the main thread in the *current* boot — just
before the SW navigates. After the reload, the new main thread reads
the previous value from the URL (if still present) and can keep the
error in the URL bar for ops visibility. Alternatively, the SW can
stash the error in IndexedDB before navigating and the new main thread
reads it on boot.

Delete all usages of the removed codes:

```bash
# Verify removed codes are no longer referenced:
docker compose run --rm node sh -c "grep -r 'ERROR_TOKEN_REFRESH_LOOP_FAILED\|ERROR_RELEASE_FILE_NOT_LOADED' assets/ || echo 'Clean'"
```

Update `docs/client/error-codes.md` (create if it doesn't exist):

```markdown
# Client Error Codes

Errors appear in the URL as `?error=ERxxx`. The status parameter
indicates the app's current phase (`init`, `login`, `running`).

## Token / auth (ER1xx)
- ER101 Token refresh failed (SW couldn't refresh, reauth required)
- ER103 Token payload missing iat/exp
- ER105 Token expired
- ER106 Token should have been refreshed by now (diagnostic)

## Service worker (ER2xx)
- ER201 SW registration failed (browser blocked or SW file unreachable)
- ER202 Watchdog triggered a reload (main thread was unresponsive)

## Crash handling (ER3xx)
- ER301 Crash loop detected (reloads suppressed during backoff)
- ER302 Uncaught exception (reload scheduled)
```

**Verify:**
```bash
docker compose run --rm node npx vitest run
docker compose run --rm playwright npx playwright test
```

**Changelog:** Changed: Error code taxonomy updated for the SW-based architecture. Removed ER102 and ER104 (dead code after refresh loop and release service removal). Added ER201 (SW registration failed), ER202 (watchdog reload), ER301 (crash loop detected), ER302 (uncaught exception). Fixed: URL error codes now clear on boot and on STATUS_RUNNING transition; persistent errors (ER301) retain their semantics. New `clearError(code)` API prevents races between delayed success handlers and newer errors.

---

## PR Dependency Graph

```
Step 0  (vitest)
  │
  ├──── Steps 1–5b  (backend fixes, independent of each other)
  │       │
  │       └──── Step 12 (response headers)
  │              │
  │              └──── Step 13 (redis sessions)
  │
  ├──── Steps 6–10  (frontend fixes, independent of each other)
  │         6a depends on existence of token-service.js and must land
  │         before Step 15 deletes that file.
  │
  ├──── Step 11 (top-level ErrorBoundary)
  │       │
  │       └──── Step 11a (config: error recovery timeouts)
  │              │
  │              ├──── Step 11b (region ErrorBoundary — reads config)
  │              │
  │              └──── Step 11c (global handlers + crash guard — reads config)
  │
  ├──── Step 14 (auth-bridge)
  │       │
  │       └──── Step 15 (service worker — reads config for watchdog/heartbeat)
  │              │
  │              └──── Step 16 (response header detection)
  │                     │
  │                     └──── Step 17 (remove polling)
  │                            │
  │                            └──── Step 17a (TRACK_SCREEN_INFO via headers)
  │                                   │
  │                                   ├──── Step 17b (real-time last-seen via Redis)
  │                                   │
  │                                   ├──── Step 17c (skip checksum on irrelevant flush)
  │                                   │
  │                                   ├──── Step 17d (diff-aware DB writes)
  │                                   │       │
  │                                   │       ├──── Step 17e (uptime via boot session)
  │                                   │       │       │
  │                                   │       │       └──── Step 17g (boot environment)
  │                                   │
  │                                   ├──── Step 17f (vitals telemetry via Redis)
  │                                   │
  │                                   ├──── Step 17h (clock skew tracking via Redis)
  │                                   │
  │                                   └──── Step 18 (error code taxonomy)
```

Steps 1–5b and 6–10 can proceed in parallel.  Step 11a must land before
11b, 11c, and 15 since they all read timeout values from the config.
Step 15's SW crash-guard module upgrades 11c's localStorage-based guard
to IndexedDB.  Step 17a bridges TRACK_SCREEN_INFO from Referer-parsing
to request headers — required because Step 17 removes the URL params
the Referer approach relied on.  Step 17c is technically independent
of 17/17a/17b (it fixes a general backend performance issue, not a
TRACK_SCREEN_INFO-specific one) but grouped nearby because the issue
surfaces most visibly under TRACK_SCREEN_INFO load.  Step 18 is the
final cleanup — after 17 removes dead code, 18 removes the dead error
codes and adds codes for new failure modes.

---

## Claude Code /loop instructions

Each step is designed to be executed as a single `/loop` session.
Copy the template below, replace `N` with the step number:

```
/loop "Implement Step N from PLAN.md against release/3.0.0.
  1. Create branch from release/3.0.0
  2. Write the failing test(s) described in the RED section
  3. Run tests inside docker:
     - PHP: docker compose run --rm phpfpm composer test -- --filter=<TestName>
     - JS:  docker compose run --rm node npx vitest run
     Confirm the new test FAILS
  4. Implement the fix described in the GREEN section
  5. Build assets if changed:
     - docker compose run --rm node npm run build
  6. Run tests — confirm all tests PASS:
     - PHP: docker compose run --rm phpfpm composer test
     - JS:  docker compose run --rm node npx vitest run
  7. Run quality checks:
     - PHP: docker compose run --rm phpfpm composer coding-standards-check
     - PHP: docker compose run --rm phpfpm composer code-analysis
     If coding standards fail: docker compose run --rm phpfpm composer coding-standards-apply
  8. Run E2E if applicable:
     - docker compose run --rm playwright npx playwright test
  9. Update CHANGELOG.md with the entry from the step
  10. Commit with message matching the changelog entry
  11. Push and create PR against release/3.0.0"
```

Each `/loop` session should take 5–15 minutes depending on scope.
