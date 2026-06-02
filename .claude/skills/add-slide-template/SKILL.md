---
name: add-slide-template
description: Add a new slide template to display-api-service — generate a ULID, create the .jsx + .json pair under assets/shared/templates/, wire the id()/config()/renderSlide() contract with slideDone() signalling, and decide between shipped-with-project vs custom-templates/ placement. Use when the user asks to add a new slide type, a new template, or extend the renderer with a new content layout.
disable-model-invocation: true
---

# Add a slide template

A slide template is the visual layout shown on a Screen. Each template is a two-file unit that the Screen Client loads and the Admin renders an editor form for. Both files must ship together.

## Where it lives

Two locations, same shape:

- `assets/shared/templates/<name>.{jsx,json}` — shipped with the project. PRs that add templates here are contributions to upstream.
- `assets/shared/custom-templates/<name>.{jsx,json}` — installation-specific templates. This folder is **gitignored** (see `.gitignore`); populate it via a fork, symlink, or per-deployment repo.

If the template is general-purpose, put it in `templates/` and consider a contribution PR (see README "Contributing template"). If it's specific to your tenant's needs, put it in `custom-templates/`.

## Files to create

### `<name>.json` — config + admin form schema

```json
{
  "title": "Display name (Danish — match existing templates' tone)",
  "id": "<ULID>",
  "options": {},
  "adminForm": [
    { "key": "<name>-form-1", "input": "header", "text": "Skabelon: <Title>", "name": "header1", "formGroupClasses": "h4 mb-3" },
    { "key": "<name>-form-2", "input": "textarea", "name": "title", "label": "Overskrift", "formGroupClasses": "col-md-6" },
    { "key": "<name>-form-3", "input": "duration", "name": "duration", "min": "1", "type": "number", "label": "Varighed (i sekunder)", "required": true, "formGroupClasses": "col-md-6 mb-3" }
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
| `duration` | Slide duration field |
| `contacts` | Contact entries |
| `feed` | Bind a feed to the slide (see "Feed integration" below) |
| `table` | Editable table |

Every `adminForm` entry needs a unique `key:` (scoped to the template is fine) and a `name:`. The `name:` is the field on `slide.content` the renderer reads.

### `<name>.jsx` — the renderer + contract

```jsx
import { useEffect } from "react";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import "../slide-utils/global-styles.css";
import myTemplateConfig from "./<name>.json";

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
  // BaseSlideExecution calls slideDone() after `content.duration` seconds.
  // For anything more complex (video end, user interaction), invoke
  // slideDone() yourself.
  useEffect(() => {
    if (!run) return;
    const exec = new BaseSlideExecution(slide, slideDone);
    exec.start();
    return () => exec.stop();
  }, [run, slide, slideDone]);

  const { title } = content;
  return <div className="my-template">{title}</div>;
}

export default { id, config, renderSlide };
```

**Critical: `slideDone()` must be called.** A template that never signals done locks the playlist on whichever screen it loads on. `BaseSlideExecution` is the standard way for fixed-duration slides; for video-driven or interactive slides, call `slideDone()` from the relevant event handler.

### Optional: `<name>/<name>.scss`

Component-scoped styles go in a sibling subfolder (see `image-text/image-text.scss` for the canonical example). Import it from the `.jsx`.

## Feed integration (optional)

If the template displays external data (RSS, calendar, events):

1. Add `{"input": "feed", "name": "feed", ...}` to `adminForm`. The Admin will show a feed-picker; the result lands at `slide.feed` and `slide.feedData`.
2. In the renderer, consume `slide.feedData` — its shape is the **feed output model** the chosen `FeedSource` produces (see `src/Feed/OutputModel/`).
3. The Client refreshes `slide.feedData` according to `CLIENT_PULL_STRATEGY_INTERVAL` (default 10 min) — the renderer doesn't need to fetch.

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

- **Forgetting `slideDone()`** — most common bug. Slide enters playlist, never advances. `BaseSlideExecution` solves the fixed-duration case.
- **Reusing a ULID** — silently overwrites the other template's registration. Always generate a fresh one.
- **adminForm `name:` doesn't match what the renderer reads** — Admin writes to `slide.content.foo`, renderer reads `slide.content.bar`. Form changes appear to do nothing.
- **Shipping only `.jsx` or only `.json`** — half-broken template. The Stop hook `claude-hook-check-template-pairs.sh` warns; the slide-template-reviewer flags as a blocker.
- **Putting custom templates in `templates/` and committing them** — they're tenant-specific; use `custom-templates/` (gitignored) or fork.

## Related

- README "Custom Templates" — full reference for `adminForm` input types and the contribution path.
- `assets/shared/custom-templates-example/` — a working example to copy from.
- `assets/shared/slide-utils/base-slide-execution.js` — the slideDone helper.
- Subagent `slide-template-reviewer` — contract checks.
