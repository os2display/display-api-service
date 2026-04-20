# Fix: Missing `currentSlide` dependency in region.jsx useEffect

## Context
In `region.jsx`, the "Make sure current slide is set" effect (line 137) reads `currentSlide` but only lists `[slides]` as a dependency. If `currentSlide` becomes `null` via `slideDone` while `slides` hasn't changed, the effect won't re-fire and the region gets stuck with no visible slide.

## Change
**File:** `assets/client/components/region.jsx:155`

Change dependency array from `[slides]` to `[slides, currentSlide]`.

This is safe because:
- The `setCurrentSlide` branch is guarded by `!currentSlide`, so it can't loop
- The `setNodeRefs` call is idempotent — rebuilds the same ref map from `slides`

## Verification
- Confirm no render loop by checking that `currentSlide` being set doesn't re-trigger the `!currentSlide` branch
- Run `task test:frontend-built` if available
