import { useEffect, useLayoutEffect, useRef, useState } from "react";
import clampDuration from "./duration.js";

// How long to hold a slide whose entries never arrived before moving on. Short
// enough not to waste screen time, long enough that a feed resolving late still
// gets a chance to start cycling.
const DEFAULT_EMPTY_ENTRIES_DURATION = 1000;

/**
 * Hook to manage slide execution for templates that cycle through
 * multiple entries (RSS feeds, news feeds, slideshows, etc.).
 *
 * Owns the whole slideDone contract, including the empty-entries case: a
 * template using this hook cannot lock the playlist by forgetting a fallback.
 *
 * @param {object} options
 * @param {Array} options.entries Array of entries to cycle through.
 * @param {number|null} options.run Run token: falsy means "do not run", and a
 *   new truthy value restarts cycling without a remount.
 * @param {object} options.slide The slide object.
 * @param {Function} options.slideDone Callback when cycling completes.
 * @param {number} options.entryDuration Duration per entry in ms. Invalid or
 *   missing falls back to DEFAULT_DURATION.
 * @param {number} [options.emptyEntriesDuration] How long to hold before
 *   finishing when there are no entries at all.
 * @returns {{currentEntry: object|null, entryIndex: number|null,
 *   entryDuration: number}} The entry being shown and its index, both null
 *   until cycling starts, plus the clamped per-entry duration so a template's
 *   own animation timers can be derived from the same number this hook uses.
 */
function useMultipleEntrySlideExecution({
  entries,
  run,
  slide,
  slideDone,
  entryDuration,
  emptyEntriesDuration = DEFAULT_EMPTY_ENTRIES_DURATION,
}) {
  // null means "not started" — an initial 0 would be indistinguishable from
  // showing the first entry, so consumers could not anchor timing to run.
  const [entryIndex, setEntryIndex] = useState(null);
  const [currentEntry, setCurrentEntry] = useState(null);

  // Refs to avoid stale closures: these are read when a timer fires, not when
  // the effect runs.
  const slideRef = useRef(slide);
  const slideDoneRef = useRef(slideDone);
  const entriesRef = useRef(entries);
  const entryDurationRef = useRef(entryDuration);

  // Layout effects run before passive effects on the same commit, so the refs
  // are current when the cycling effect below reads them synchronously.
  useLayoutEffect(() => {
    slideRef.current = slide;
    slideDoneRef.current = slideDone;
    entriesRef.current = entries;
    entryDurationRef.current = entryDuration;
  });

  // Depend on whether there are entries at all, not on the array itself:
  // consumers rebuild the array every render, so depending on its identity
  // would restart cycling continuously. This boolean only flips when a feed
  // resolves (or empties), which is exactly when cycling should (re)start.
  const hasEntries = (entries?.length ?? 0) > 0;

  useEffect(() => {
    if (!run) {
      setEntryIndex(null);
      setCurrentEntry(null);
      return undefined;
    }

    let timeoutId = null;
    let stopped = false;

    // No entries: hold briefly, then let the playlist move on. Owned here so no
    // consumer has to remember it.
    if (!hasEntries) {
      timeoutId = setTimeout(() => {
        slideDoneRef.current(slideRef.current);
      }, clampDuration(emptyEntriesDuration));

      return () => {
        stopped = true;
        clearTimeout(timeoutId);
      };
    }

    const showEntry = (index) => {
      if (stopped) return;

      if (index >= entriesRef.current.length) {
        slideDoneRef.current(slideRef.current);
        return;
      }

      setEntryIndex(index);
      setCurrentEntry(entriesRef.current[index]);

      timeoutId = setTimeout(
        () => showEntry(index + 1),
        clampDuration(entryDurationRef.current),
      );
    };

    showEntry(0);

    return () => {
      stopped = true;
      if (timeoutId !== null) {
        clearTimeout(timeoutId);
      }
    };
  }, [run, hasEntries, emptyEntriesDuration]);

  return {
    currentEntry,
    entryIndex,
    entryDuration: clampDuration(entryDuration),
  };
}

export default useMultipleEntrySlideExecution;
