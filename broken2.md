# Fix: `newSlides` not cleared when consumed by the "no current slide" path

## Context
In `region.jsx`, the effect at line 130–134 consumes `newSlides` by calling `setSlides(newSlides)` when `currentSlide` is null, but never clears `newSlides` afterwards. This means `newSlides` stays non-null. Later, when `slideDone` wraps at `nextIndex === 0`, it checks `Array.isArray(latestNewSlides)` — which is still true — and re-applies the same stale slides unnecessarily, causing a redundant re-render cycle.

## Change
**File:** `assets/client/components/region.jsx:132`

Add `setNewSlides(null)` after `setSlides(newSlides)`:

```js
useEffect(() => {
    if (newSlides !== null && !currentSlide) {
      setSlides(newSlides);
      setNewSlides(null);
    }
}, [newSlides, currentSlide]);
```

This mirrors the same pattern used in `slideDone` (line 81–82) where `setSlides` and `setNewSlides(null)` are always paired.

## Verification
- Run `task test:unit`
- The existing "wraps around to first slide after last" test exercises the `slideDone` wrap path and should still pass
