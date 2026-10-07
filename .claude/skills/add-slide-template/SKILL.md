---
name: add-slide-template
description: Add a new slide template to display-api-service — generate a ULID, create the .jsx + .json pair under assets/shared/templates/, wire the id()/config()/renderSlide() contract with slideDone() signalling, and decide between shipped-with-project vs custom-templates/ placement. Use when the user asks to add a new slide type, a new template, or extend the renderer with a new content layout.
disable-model-invocation: true
---

# Add a slide template

A slide template is the visual layout shown on a Screen. Each template is a two-file unit that the Screen Client loads and the Admin renders an editor form for. Both files must ship together.

## Where it lives

Two locations, same shape:

- `assets/shared/templates/<template-name>.{jsx,json}` — shipped with the project. PRs that add templates here are contributions to upstream.
- `assets/shared/custom-templates/<template-name>.{jsx,json}` — installation-specific templates. This folder is **gitignored** (see `.gitignore`); populate it via a fork, symlink, or per-deployment repo.

If the template is general-purpose, put it in `templates/` and consider a contribution PR (see README "Contributing template"). If it's specific to your tenant's needs, put it in `custom-templates/`.

## Files to create

### `<template-name>.json` — config + admin form schema

```json
{
  "title": "Display name (Danish — match existing templates' tone)",
  "id": "<ULID>",
  "options": {},
  "adminForm": [
    { "key": "<template-name>-form-1", "input": "header", "text": "Skabelon: <Title>", "name": "header1", "formGroupClasses": "h4 mb-3" },
    { "key": "<template-name>-form-2", "input": "textarea", "name": "title", "label": "Overskrift", "formGroupClasses": "col-md-6" },
    { "key": "<template-name>-form-3", "input": "duration", "name": "duration", "min": "1", "type": "number", "label": "Varighed (i sekunder)", "required": true, "formGroupClasses": "col-md-6 mb-3" }
  ]
}
```

**ULID generation** — 26 chars, Crockford base32 (excludes `I/L/O/U`). Never reuse another template's ULID; never change a published template's ULID. Quick generation:

```shell
docker compose exec phpfpm php -r 'echo (new Symfony\Component\Uid\Ulid()) . "\n";'
```

Or any online ULID generator — just paste the result into `id`.

**`adminForm` input types** (defined by the Admin renderer in `assets/admin/components/slide/content/`):

| Input | Purpose |
|---|---|
| `header`, `header-h3` | Section headings (`text:` is the heading) |
| `input` | Plain HTML5 input; combine with `type: "text" \| "number" \| "email"` |
| `textarea` | Multi-line text |
| `rich-text-input` | HTML editor (tiptap) |
| `select` | Dropdown; needs `options: [{key, title, value}]` |
| `checkbox` | Boolean toggle |
| `image` / `video` / `file` | Media picker (set `multipleImages: true` for image arrays) |
| `duration` | Slide duration field — see units note below |
| `contacts` | Contact entries |
| `feed` | Bind a feed to the slide (see "Feed integration" below) |
| `table` | Editable table |

Every `adminForm` entry needs a unique `key:` (scoped to the template is fine) and a `name:`. The `name:` is the field on `slide.content` the renderer reads.

**Exception — the `duration` input must be named `duration`.** The Admin's duration widget hardcodes its write target (`content-form.jsx` writes to `id: "duration"` regardless of `name:`), so any other name silently stores nothing where the renderer looks.

**Duration units.** The Admin's `duration` field shows **seconds** in the UI but stores **milliseconds** in `slide.content.duration` (×1000 on write, ÷1000 on display). The renderer therefore reads ms — that's why the jsx below defaults to `15000` while the form label says "i sekunder".

### `<template-name>.jsx` — the renderer + contract

```jsx
import useBaseSlideExecution from "../slide-utils/useBaseSlideExecution.js";
import "../slide-utils/global-styles.css";
import myTemplateConfig from "./<template-name>.json";

function id() {
  return myTemplateConfig.id;
}

function config() {
  return myTemplateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <MyTemplate
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

function MyTemplate({ slide, run, slideDone, content, executionId }) {
  const { title, duration = 15000 } = content; // ms — the Admin stores ms

  // Calls slideDone(slide) once `duration` ms have passed since `run` became
  // truthy. A *new* truthy `run` value restarts the timer without a remount —
  // that's how a single-slide region replays the slide. The timer is cleared
  // on unmount, and an invalid or missing duration falls back to 15000 ms.
  // For anything more complex (video end, user interaction), invoke
  // slideDone() yourself.
  useBaseSlideExecution({ slide, run, slideDone, duration });

  return <div className="my-template">{title}</div>;
}

export default { id, config, renderSlide };
```

**Critical: `slideDone()` must be called.** A template that never signals done locks the playlist on whichever screen it loads on. `useBaseSlideExecution` is the standard way for fixed-duration slides. For video-driven or interactive slides, call `slideDone()` yourself from the relevant event handler — and make sure **every** path reaches it exactly once: see `templates/video.jsx` for the canonical guard pattern (`ended`/`error` listeners plus a metadata timeout plus a duration-based backstop, all funnelled through an idempotent `finish()`).

#### Templates that cycle through entries

If the template steps through a list (feed entries, images) before signalling done, use
`useMultipleEntrySlideExecution` instead — it owns the cycling and calls `slideDone()` after the last
entry:

```jsx
const { currentEntry, entryIndex } = useMultipleEntrySlideExecution({
  entries,
  run,
  slide,
  slideDone,
  entryDuration, // ms per entry — see units note below
});
```

**`entryDuration` is in milliseconds.** Feed configurations typically store **seconds** — convert at
the template boundary, as every in-tree consumer does (`entryDuration * 1000` in `rss.jsx` and
`news-feed.jsx`). Passing raw seconds is not caught by the fallback: `10` is a valid positive
number, so each entry displays for 10 ms and the slide flashes past. Only invalid values (missing,
zero, negative, non-numeric) fall back to 15000 ms.

`currentEntry` and `entryIndex` are both `null` until the slide starts running, so guard on that
rather than assuming index `0` — anchoring your own timers or counters to mount instead of to `run`
is the classic bug here. See `rss.jsx`, `slideshow.jsx`, `news-feed.jsx`, `instagram-feed.jsx` and
`poster.jsx` for the five in-tree usages. The hook is a no-op on an empty `entries` array; templates
add their own short fallback timer for that case.

### Optional: `<template-name>/<template-name>.scss`

Component-scoped styles go in a sibling subfolder (see `image-text/image-text.scss` for the canonical example). Import it from the `.jsx`.

## Feed integration (optional)

If the template displays external data (RSS, calendar, events):

1. Add `{"input": "feed", "name": "feed", ...}` to `adminForm`. The Admin will show a feed-picker; the result lands at `slide.feed` and `slide.feedData`.
2. In the renderer, consume `slide.feedData` — its shape is the **feed output model** the chosen `FeedSource` produces (see `src/Feed/OutputModel/`).
3. The Client refreshes `slide.feedData` according to `CLIENT_PULL_STRATEGY_INTERVAL` (default 10 min) — the renderer doesn't need to fetch.
4. Feed configuration values like `entryDuration` arrive in **seconds** — multiply by 1000 before handing them to a hook.

Templates are decoupled from feed implementations via the output-model contract. A new feed source that produces the same output model can power any existing template — no template changes required. See README "Feeds" for the architecture.

## Build & test

After saving the two files:

```shell
task assets:build
```

This compiles the templates into the asset bundle. The Client picks them up on next refresh.

To preview interactively (dev mode), visit `http://<base-url>/template` — it renders every template against the fixtures in `fixtures/`.

For automated checks:

```shell
task test:unit           # Vitest on any *.test.jsx alongside the template
task test:frontend-local # Playwright if you added a *.spec.js
```

## Validation

The `slide-template-reviewer` subagent checks the contract — invoke it after creating/changing template files. It catches the common bugs (missing `slideDone()` call, ULID duplicate, adminForm/renderer key drift, wrong `renderSlide` signature).

## Common mistakes

- **Forgetting `slideDone()`** — most common bug. Slide enters playlist, never advances. `useBaseSlideExecution` solves the fixed-duration case, `useMultipleEntrySlideExecution` the cycle-then-done case.
- **Passing seconds where a hook expects milliseconds** — `entryDuration: 10` shows each entry for 10 ms; the slide is gone almost instantly and no fallback rescues it. Convert feed-config seconds at the template boundary.
- **Anchoring timers to mount instead of `run`** — a single-slide region replays by issuing a new `run` value without remounting; timers keyed to mount never restart. Key everything to `run`.
- **Reusing a ULID** — silently overwrites the other template's registration. Always generate a fresh one.
- **adminForm `name:` doesn't match what the renderer reads** — Admin writes to `slide.content.foo`, renderer reads `slide.content.bar`. Form changes appear to do nothing. (And the `duration` input only ever writes to `slide.content.duration`.)
- **Shipping only `.jsx` or only `.json`** — half-broken template. The Stop hook `claude-hook-check-template-pairs.sh` warns; the slide-template-reviewer flags as a blocker.
- **Putting custom templates in `templates/` and committing them** — they're tenant-specific; use `custom-templates/` (gitignored) or fork.

## Related

- README "Custom Templates" — full reference for `adminForm` input types and the contribution path.
- `assets/shared/custom-templates-example/` — a working example to copy from.
- `assets/shared/slide-utils/useBaseSlideExecution.js` — the fixed-duration slideDone helper.
- `assets/shared/slide-utils/useMultipleEntrySlideExecution.js` — the cycle-through-entries helper.
- `assets/shared/templates/video.jsx` — the guard pattern for self-managed (event-driven) slideDone.
- Subagent `slide-template-reviewer` — contract checks.
