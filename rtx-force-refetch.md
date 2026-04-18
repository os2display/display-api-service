# Fix: Merge relationChecksum with RTK Query forceRefetch

## Context

`PullStrategy` manually caches composed data in `this.latestScreenData` and uses checksum comparisons to decide whether to refetch or reuse cached data. This duplicates what RTK Query already provides — a per-endpoint cache. The `query()` function also lacks `forceRefetch`, so RTK Query silently serves stale data on every poll after the first.

## Approach

Use checksums to control `forceRefetch` on `query()` calls. RTK Query becomes the sole cache. Remove `this.latestScreenData` as a data cache and the manual if/else branching.

**File**: `assets/client/data-sync/pull-strategy.js`

### 1. Update `query()` to accept `forceRefetch`

```js
function query(endpoint, args, forceRefetch = false) {
  return clientStore
    .dispatch(
      clientApi.endpoints[endpoint].initiate(args, { forceRefetch })
    )
    .unwrap();
}
```

`queryAllPages()` also needs the parameter, passed through to `query()`.

### 2. Store only previous checksums, not full data

Replace `this.latestScreenData` with two lightweight stores:

```js
this.previousScreenChecksums = null;   // { campaigns, inScreenGroups, layout, regions }
this.previousSlideChecksums = {};      // { [slideId]: { templateInfo, media } }
```

Updated at end of `getScreen()` from the fetched data.

### 3. Screen fetch — always forceRefetch

```js
screen = await query("getV2ScreensById", { id: idFromPath(screenPath) }, true);
```

This is the root of the sync cycle — must always hit the network to get fresh checksums.

### 4. Remove all manual cache branches, use forceRefetch instead

For each downstream resource, compute whether the checksum changed, pass that as `forceRefetch`:

**Campaigns** (currently lines 286-297):
```js
const campaignsChanged =
  !relationChecksumEnabled ||
  !this.previousScreenChecksums ||
  this.previousScreenChecksums.campaigns !== newScreenChecksums.campaigns ||
  this.previousScreenChecksums.inScreenGroups !== newScreenChecksums.inScreenGroups;

// Always call getCampaignsData — it will hit cache or network based on forceRefetch
newScreen.campaignsData = await this.getCampaignsData(newScreen, campaignsChanged);
```

- `getCampaignsData` passes `forceRefetch` through to `query()` / `queryAllPages()`
- Remove the `else { ... loaded from cache }` branch entirely

**Layout** (currently lines 340-368):
```js
const layoutChanged =
  !relationChecksumEnabled ||
  !this.previousScreenChecksums ||
  this.previousScreenChecksums.layout !== newScreenChecksums.layout;

newScreen.layoutData = await query("getV2LayoutsById", { id: idFromPath(newScreen.layout) }, layoutChanged);
```

**Regions + slides** (currently lines 371-383):
```js
const regionsChanged =
  !relationChecksumEnabled ||
  !this.previousScreenChecksums ||
  this.previousScreenChecksums.regions !== newScreenChecksums.regions;

// getRegions and getSlidesForRegions pass forceRefetch through
const regions = await this.getRegions(newScreen.regions, regionsChanged);
newScreen.regionData = await this.getSlidesForRegions(regions, regionsChanged);
```

**Per-slide template/media** (currently lines 424-502):
```js
const templateChanged =
  !relationChecksumEnabled ||
  !this.previousSlideChecksums[slideId] ||
  newSlideChecksums.templateInfo !== this.previousSlideChecksums[slideId]?.templateInfo;

slide.templateData = await query("getV2TemplatesById", { id: templateId }, templateChanged);
```

Same pattern for media. RTK Query's cache naturally deduplicates same-template/same-media across slides within a cycle (same endpoint + args = cache hit), so `fetchedTemplates` / `fetchedMedia` maps can be removed.

**Feed** — always forceRefetch (no checksum, needs fresh data):
```js
slide.feedData = await query("getV2FeedsByIdData", { id: idFromPath(slide.feed.feedUrl) }, true);
```

### 5. Update at end of cycle

```js
this.previousScreenChecksums = newScreen.relationsChecksum ?? null;
this.previousSlideChecksums = {}; // rebuild from current slide data
// ... iterate slides and store { [slideId]: slide.relationsChecksum }
```

### 6. Handle `hasActiveCampaign` transition

Current code (line 342) also forces layout refetch when `this.latestScreenData?.hasActiveCampaign` (transitioning from campaign → normal). Store `this.previousHadActiveCampaign` boolean alongside checksums.

### 7. Methods that need signature changes

| Method | New parameter | Passes to |
|--------|--------------|-----------|
| `query()` | `forceRefetch = false` | `initiate()` |
| `queryAllPages()` | `forceRefetch = false` | `query()` |
| `getCampaignsData()` | `forceRefetch` | `query()`, `queryAllPages()` |
| `getRegions()` | `forceRefetch` | `queryAllPages()` |
| `getSlidesForRegions()` | `forceRefetch` | `queryAllPages()` |

### Summary of removals

- `this.latestScreenData` property → replaced by `this.previousScreenChecksums`, `this.previousSlideChecksums`, `this.previousHadActiveCampaign`
- All `else { ... loaded from cache }` branches (6 of them)
- `fetchedTemplates` / `fetchedMedia` within-cycle maps (RTK Query deduplicates naturally)
- `previousSlide` lookup and `cloneDeep` of previous slide data

## Verification

- `task assets:build` succeeds
- `task test:frontend-built` passes
- Manual: content updates on screen are picked up within polling interval
- Manual: unchanged content does NOT trigger unnecessary network requests (check DevTools network tab)
