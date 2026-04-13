# Evaluation of rtx-migration.md

## Overall Assessment

The plan is **well-researched and largely correct** in its assumptions. All 16 endpoint names were verified in `generated-api.ts`, the authentication flow is accurately described, and the architectural decisions are sound. However, there are several issues and risks worth addressing before execution.

---

## Verified Correct

- All RTK Query endpoint names exist exactly as specified (including lowercase `getv2MediaById`)
- Pagination support exists on the correct collection endpoints (`page`, `itemsPerPage` params)
- `extended-base-query.js` does import from `assets/admin/components/util/local-storage-keys.jsx` (confirming it's admin-specific)
- Client uses `apiToken` / `tenantKey` localStorage keys (plain strings, not JSON)
- 401 handling dispatches `document.dispatchEvent(new Event("reauthenticate"))`
- `id-from-path.js` exists and uses regex `/[A-Za-z0-9]{26}/`
- `content-service.js` preview methods use `pullStrategy.getPath()` and `attachReferencesToSlide()` as described

---

## Issues and Concerns

### 1. Phase 0 is large and risky — should be a separate PR

Moving shared Redux to `assets/admin/redux/` touches **42+ admin component files** plus the entry point. This is a purely mechanical refactor that has nothing to do with the client migration. Bundling it into the same work:
- Makes the PR enormous and hard to review
- Risks introducing import breakage in admin while the actual goal is client changes
- **Recommendation**: Do Phase 0 as a standalone PR first. Or skip it entirely for now — the "shared" directory being admin-specific is a naming issue, not a blocking problem for the client migration.

### 2. RTK Query caching may interfere with the pull-based sync

RTK Query caches responses by endpoint + args. When `PullStrategy` polls on an interval, subsequent calls to the same endpoint will hit RTK Query's cache instead of making a network request. This is a **fundamental behavioral change** from raw `fetch()`.

Options:
- Use `forceRefetch: true` on every `initiate()` call — but this defeats RTK Query's purpose
- Use `initiate(args, { forceRefetch: true })` only for the polling cycle, not for preview
- Set a very short `keepUnusedDataFor` on the client API
- **Recommendation**: This needs explicit design. The plan should specify the caching strategy. For a pull-based sync pipeline that expects fresh data each cycle, `forceRefetch: true` or `{ subscribe: false, forceRefetch: true }` is likely needed.

### 3. Error handling semantics change

Current `ApiHelper.getPath()` returns `null` on failure (line ~50 of api-helper.js). RTK Query's `.unwrap()` **throws** on error. Every call site that checks `if (result === null)` will need to be converted to try/catch. The plan doesn't mention this behavioral difference.

**Affected areas**: PullStrategy checks for null results in multiple places to mark slides as invalid or skip processing.

### 4. Bundle size impact on display devices

Adding Redux + RTK Query + 26K lines of generated endpoints to the client bundle is non-trivial. The client runs on display devices (screens, kiosks) that may have limited resources. The current raw-fetch approach is intentionally lightweight.

**Recommendation**: Measure the bundle size delta before and after. Consider whether the client actually needs the full generated API or just a subset of endpoints.

### 5. Duplicated generated-api.ts (26K+ lines x 2)

The plan generates a second copy of `generated-api.ts` for the client. This means:
- Two 26K+ line files to maintain
- Two codegen commands to run
- Potential drift if one is regenerated and the other isn't

**Alternative**: Keep one `generated-api.ts` in shared, but have each app provide its own `empty-api.ts` with its own base query. The codegen output is just endpoint definitions — the base query is injected via the empty API. This would require the codegen to support a shared output with pluggable base queries, which RTK Query codegen doesn't natively support. So the duplication may be unavoidable, but the Taskfile should run both codegen commands together.

### 6. `content-service.js` preview creates PullStrategy for `getPath()` convenience

In preview mode (lines 212-246), `content-service.js` creates a `PullStrategy` instance solely to use its `getPath()`, `getTemplateData()`, `getFeedData()`, and `getMediaData()` helper methods. After migration, these methods won't exist on PullStrategy anymore.

The plan addresses this (Phase 3.2) but the replacement pattern needs the `query()` helper to be importable from outside PullStrategy — it should be a shared utility in `assets/client/redux/` or `assets/client/util/`, not local to PullStrategy.

### 7. `credentials: "include"` for screen auth

The plan correctly notes this (Phase 2.2) but buries it as a caveat. This is a **must-have** configuration in `fetchBaseQuery`. If forgotten, screen binding will silently fail. It should be a top-level action item in Phase 1.1.

### 8. Missing: `getPath()` on PullStrategy used by content-service

`PullStrategy.getPath(path)` is a public method called externally by content-service.js (lines 216, 218, 246, 281). The plan says "Remove ApiHelper entirely" but doesn't explicitly address that `getPath` is a PullStrategy public API method, not just an internal one. It's used as `pullStrategy.getPath(url)` where `url` is a full path like `/v2/playlists/{id}`. This is handled in Phase 3.2, but it's worth noting that PullStrategy's public interface changes.

---

## Design Decisions — Agree/Disagree

| Decision | Verdict | Comment |
|---|---|---|
| Each app owns its Redux | **Agree** | Different auth, different needs |
| Preserve event-driven architecture | **Agree** | Rewriting to React state is out of scope |
| Preserve checksum optimization | **Agree** | RTK Query tags don't replace server-side checksums |
| `store.dispatch(initiate())` vs hooks | **Agree** | Class-based services can't use hooks |
| Own codegen config | **Agree, with caveat** | Ensure Taskfile runs both together |

---

## Suggested Execution Order

1. **Phase 0 as separate PR** (or defer entirely)
2. **Phase 1** — Client Redux infrastructure (with explicit `credentials: "include"` and cache policy)
3. **Phase 2** — Simple services (tenant + token), with error handling changes
4. **Phase 3** — Data sync pipeline (largest, most risk — could be its own PR)

Each phase should be independently testable with `task assets:build` and `task test:frontend-built`.

---

## Verification Additions

The plan's verification section is good. Add:
- Bundle size comparison (before/after)
- Verify RTK Query doesn't serve stale cached data during sync cycles
- Test 401 → reauthenticate → token refresh → resume flow specifically
- Test screen binding flow with `credentials: "include"`
