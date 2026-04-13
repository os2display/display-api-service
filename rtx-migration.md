# Plan: Replace Custom Fetches in Client with RTK Query

## Context

The client app (`assets/client/`) uses raw `fetch()` calls in service classes and a custom pull-based data sync pipeline. The shared RTK Query infrastructure (`enhanced-api.ts`, `generated-api.ts`, `store.js`) already has typed endpoints for every API call the client makes, but:
- The client has **no Redux store or Provider** (`index.jsx` renders `<App>` directly)
- The shared `extended-base-query.js` is **admin-specific** (reads `admin-api-token` and JSON-parsed `admin-selected-tenant` from localStorage)
- The client uses different localStorage keys (`apiToken`, `tenantKey`) via its own `local-storage-keys.js`

**Goal:** Replace custom `fetch()` calls in the client with RTK Query endpoints from the enhanced API.

**Out of scope:** `client-config-loader.js` (`GET /config/client`) and `release-loader.js` (`GET /release.json`) — these are infrastructure endpoints, not API data endpoints.

---

## Phase 0: Reorganize Redux — Move Admin-Specific Code to `assets/admin/redux/`

Currently all Redux files live in `assets/shared/redux/`. The admin-specific files should move to `assets/admin/redux/`:

### 0.0 Move admin Redux files

| From (`assets/shared/redux/`) | To (`assets/admin/redux/`) |
|------|------|
| `extended-base-query.js` | `base-query.js` |
| `empty-api.ts` | `empty-api.ts` |
| `generated-api.ts` | `generated-api.ts` |
| `enhanced-api.ts` | `enhanced-api.ts` |
| `store.js` | `store.js` |
| `openapi-config.js` | `openapi-config.js` |

**What stays in `assets/shared/redux/`:** Nothing — the shared directory can be removed once both admin and client have their own Redux setups. (Or keep it if there's genuinely shared Redux code later.)

**Update imports:** All admin files that import from `../../shared/redux/` need import paths updated to `../redux/` (or similar relative path). Key files:
- `assets/admin/index.jsx` (imports `store`)
- All admin components that import hooks from `enhanced-api.ts`
- `extended-base-query.js` → `base-query.js` imports `local-storage-keys.jsx` from admin (already does)

**Update codegen:** `openapi-config.js` paths (`schemaFile`, `apiFile`, `outputFile`) need adjusting for the new location.

---

## Phase 1: Client-Specific Redux Infrastructure

### 1.1 Create `assets/client/redux/base-query.js`

A client-specific base query (similar to `assets/shared/redux/extended-base-query.js`) that:
- Reads token from `localStorage.getItem("apiToken")` (client key)
- Reads tenant key from `localStorage.getItem("tenantKey")` (client key, plain string — not JSON like admin)
- Checks URL params `preview-token` and `preview-tenant` and uses those when present (currently in `api-helper.js:31-37`)
- Sets headers: `authorization: Bearer {token}`, `Authorization-Tenant-Key: {tenantKey}`, `accept: application/ld+json`
- On 401: dispatches `document.dispatchEvent(new Event("reauthenticate"))` (same as current `api-helper.js:54`)
- Does NOT include admin-specific param rewriting (order, exists, etc.)

Reference: `assets/shared/redux/extended-base-query.js`

### 1.2 Create `assets/client/redux/empty-api.ts`

```ts
import { createApi } from '@reduxjs/toolkit/query/react';
import clientBaseQuery from "./base-query";

export const clientEmptySplitApi = createApi({
  reducerPath: 'clientApi',
  baseQuery: clientBaseQuery,
  endpoints: () => ({}),
});
```

### 1.3 Generate client endpoint definitions

Add a codegen config `assets/client/redux/openapi-config.js` pointing to the client empty API:
```js
const config = {
  schemaFile: "../../../public/api-spec-v2.json",
  apiFile: "./empty-api.ts",
  apiImport: "clientEmptySplitApi",
  outputFile: "./generated-api.ts",
  exportName: "clientApi",
  hooks: true,
  tag: true,
  endpointOverrides: [ /* same as shared/redux/openapi-config.js */ ],
};
```

Run `npx @rtk-query/codegen-openapi openapi-config.js` to generate `assets/client/redux/generated-api.ts`. Add this as a task in Taskfile or as a companion step to `task generate:redux-toolkit-api`.

### 1.4 Create `assets/client/redux/enhanced-api.ts`

Mirror of `assets/shared/redux/enhanced-api.ts` but importing from the client's `generated-api.ts`. Only needs the invalidation rules relevant to client (most mutations aren't used by client, so this can be minimal).

### 1.5 Create `assets/client/redux/store.js`

```js
import { configureStore } from "@reduxjs/toolkit";
import { clientApi } from "./generated-api.ts";

export const clientStore = configureStore({
  reducer: { [clientApi.reducerPath]: clientApi.reducer },
  middleware: (gDM) => gDM().concat(clientApi.middleware),
});
```

### 1.6 Add Redux Provider in `assets/client/index.jsx`

Wrap `<App>` with `<Provider store={clientStore}>`.

**Files created/modified:**
- `assets/client/redux/base-query.js` (new)
- `assets/client/redux/empty-api.ts` (new)
- `assets/client/redux/openapi-config.js` (new)
- `assets/client/redux/generated-api.ts` (new, auto-generated)
- `assets/client/redux/enhanced-api.ts` (new)
- `assets/client/redux/store.js` (new)
- `assets/client/index.jsx` (modified)

---

## Phase 2: Migrate Simple Services

### 2.1 Migrate `assets/client/service/tenant-service.js`

**Current:** `fetch('/v2/tenants/${tenantId}', ...)` with manual auth headers (line 12)
**Replace with:** `clientStore.dispatch(clientApi.endpoints.getV2TenantsById.initiate({ id: tenantId })).unwrap()` (import from `../redux/store.js` and `../redux/generated-api.ts`)

The response shape is the same. Error handling maps directly (`.catch()`).

### 2.2 Migrate `assets/client/service/token-service.js`

Two fetch calls:

1. **`refreshToken()`** (line 88): `POST /v2/authentication/token/refresh`
   - Replace with: `clientStore.dispatch(clientApi.endpoints.postRefreshTokenItem.initiate({ ... })).unwrap()`
   - Note: verify the generated endpoint's expected body shape matches `{ refresh_token }`.

2. **`checkLogin()`** (line 166): `POST /v2/authentication/screen`
   - Replace with: `clientStore.dispatch(clientApi.endpoints.postLoginInfoScreen.initiate({ ... })).unwrap()`
   - **Caveat:** Current code uses `credentials: "include"` (cookie-based screen auth). The `client-base-query.js` must set `credentials: "include"` in its `fetchBaseQuery` config to support this.

**Files modified:**
- `assets/client/service/tenant-service.js`
- `assets/client/service/token-service.js`

---

## Phase 3: Migrate Data Sync Pipeline

This is the largest change. The strategy: replace the transport layer (`ApiHelper`) while preserving the orchestration logic (`PullStrategy`).

### 3.1 Refactor `assets/client/data-sync/pull-strategy.js` — use named endpoints directly

Remove `ApiHelper` entirely. Each fetch call in PullStrategy becomes a direct RTK Query endpoint dispatch. Use `idFromPath()` (existing in `assets/client/util/id-from-path.js`) to extract IDs from Hydra paths.

Helper for dispatching (local to file or small util):
```js
async function query(endpoint, args) {
  return clientStore.dispatch(clientApi.endpoints[endpoint].initiate(args)).unwrap();
}
```

Call-by-call migration in `getScreen()` and helpers:

| Current code | Becomes |
|---|---|
| `this.apiHelper.getPath(screenPath)` | `query("getV2ScreensById", { id: idFromPath(screenPath) })` |
| `this.apiHelper.getPath(screen.inScreenGroups)` | `query("getV2ScreensByIdScreenGroups", { id: idFromPath(screen["@id"]) })` |
| `this.apiHelper.getAllResultsFromPath(group.campaigns)` | `query("getV2ScreenGroupsByIdCampaigns", { id: idFromPath(group["@id"]) })` — handle pagination with `page` param |
| `this.apiHelper.getPath(screen.campaigns)` | `query("getV2ScreensByIdCampaigns", { id: idFromPath(screen["@id"]) })` |
| `this.apiHelper.getPath(newScreen.layout)` | `query("getV2LayoutsById", { id: idFromPath(newScreen.layout) })` |
| `this.apiHelper.getAllResultsFromPath(regionPath)` | `query("getV2ScreensByIdRegionsAndRegionIdPlaylists", { id: screenId, regionId: extractRegionId(regionPath) })` — extract both IDs with regex |
| `this.apiHelper.getAllResultsFromPath(playlist.slides, keys)` | `query("getV2PlaylistsByIdSlides", { id: idFromPath(playlist["@id"]) })` — handle pagination |
| `this.apiHelper.getPath(templatePath)` | `query("getV2TemplatesById", { id: idFromPath(templatePath) })` |
| `this.apiHelper.getPath(mediaId)` | `query("getv2MediaById", { id: idFromPath(mediaId) })` |
| `this.apiHelper.getPath(slide.feed.feedUrl)` | `query("getV2FeedsByIdData", { id: idFromPath(slide.feed.feedUrl) })` |

For the two-ID regions path (`/v2/screens/{id}/regions/{regionId}/playlists`), add a small `extractRegionId(path)` helper using the existing regex in `getRegions()` at line 106.

Pagination: Current `getAllResultsFromPath` loops through `?page=N`. Replace with a local `queryAllPages(endpoint, args)` helper that calls the endpoint with incrementing `page` param, collecting `hydra:member` results.

The checksum comparison logic, caching, and event dispatch all stay unchanged — only the transport calls change.

### 3.2 Refactor `assets/client/service/content-service.js` preview methods

`startPreview()` and `attachReferencesToSlide()` currently create ad-hoc `PullStrategy` instances for one-off fetches via `pullStrategy.getPath(...)`. Replace with direct endpoint dispatches:

| Current | Becomes |
|---|---|
| `pullStrategy.getPath("/v2/playlists/" + id)` | `query("getV2PlaylistsById", { id })` |
| `pullStrategy.getPath("/v2/slides/" + id)` | `query("getV2SlidesById", { id })` |
| `strategy.getTemplateData(slide)` | `query("getV2TemplatesById", { id: idFromPath(slide.templateInfo["@id"]) })` |
| `strategy.getFeedData(slide)` | `query("getV2FeedsByIdData", { id: idFromPath(slide.feed.feedUrl) })` |
| `strategy.getMediaData(media)` | `query("getv2MediaById", { id: idFromPath(media) })` |
| `strategy.getPath(slide.theme)` | `query("getV2ThemesById", { id: idFromPath(slide.theme) })` |

### 3.3 Delete `assets/client/data-sync/api-helper.js`

No longer needed.

### 3.4 Clean up `assets/client/data-sync/data-sync.js`

Update if needed — it wraps PullStrategy construction. May simplify since `ApiHelper` endpoint config is no longer passed.

**Files modified/deleted:**
- `assets/client/data-sync/pull-strategy.js` (major refactor — replace all ApiHelper calls with named endpoints)
- `assets/client/service/content-service.js` (refactor preview methods)
- `assets/client/data-sync/api-helper.js` (deleted)
- `assets/client/data-sync/data-sync.js` (minor update)

---

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Each app owns its Redux in `assets/{app}/redux/` | Yes | Admin and client use different auth. No shared Redux folder needed. |
| Preserve event-driven architecture | Yes | ContentService/ScheduleService/Region event pipeline works well. Rewriting to React state is a separate concern. |
| Preserve checksum optimization | Yes | RTK Query's tag-based invalidation doesn't replace server-side relationsChecksum polling. Keep application-level caching logic. |
| Use `store.dispatch(initiate())` vs hooks | `dispatch(initiate())` | Services are class-based singletons outside React. Hooks only work in components. |
| How to generate client endpoints | Own codegen config in `assets/client/redux/` | Avoids manual duplication. Same OpenAPI spec, different base query. One extra codegen step. |

---

## Verification

1. **Build:** `task assets:build` — ensure no TypeScript/bundling errors
2. **E2E tests:** `task test:frontend-built` — existing Playwright tests cover screen display flow
3. **Manual testing:**
   - Start dev server, open client URL
   - Verify screen binding flow works (login → bind key → content display)
   - Verify content polling loads and updates slides
   - Verify token refresh happens silently
   - Verify preview mode works (screen, playlist, slide previews from admin)
   - Check browser DevTools Network tab: requests should still go to same endpoints with same headers
4. **Regression:** Verify admin app still works (its API slice is untouched)
