import { useEffect, useRef, useState } from "react";

/**
 * Hook to manage slide execution for templates that cycle through
 * multiple entries (RSS feeds, news feeds, slideshows, etc.).
 *
 * Uses refs to avoid stale closures while keeping [run] as the
 * only dependency that triggers the cycling.
 *
 * @param {object} options
 * @param {Array} options.entries Array of entries to cycle through.
 * @param {boolean} options.run Whether the slide should run.
 * @param {object} options.slide The slide object.
 * @param {Function} options.slideDone Callback when cycling completes.
 * @param {number} options.entryDuration Duration per entry in ms.
 */
function useMultipleEntrySlideExecution({
  entries,
  run,
  slide,
  slideDone,
  entryDuration,
}) {
  const [entryIndex, setEntryIndex] = useState(0);
  const [currentEntry, setCurrentEntry] = useState(null);

  // Refs to avoid stale closures (same pattern as useBaseSlideExecution)
  const slideRef = useRef(slide);
  const slideDoneRef = useRef(slideDone);
  const entriesRef = useRef(entries);
  const entryDurationRef = useRef(entryDuration);

  slideRef.current = slide;
  slideDoneRef.current = slideDone;
  entriesRef.current = entries;
  entryDurationRef.current = entryDuration;

  useEffect(() => {
    if (!run || !entriesRef.current?.length) return;

    let timeoutId = null;
    let stopped = false;

    const showEntry = (index) => {
      if (stopped) return;

      if (index >= entriesRef.current.length) {
        slideDoneRef.current(slideRef.current);
        return;
      }

      setEntryIndex(index);
      setCurrentEntry(entriesRef.current[index]);

      const safeDuration =
        Number.isFinite(entryDurationRef.current) &&
        entryDurationRef.current > 0
          ? entryDurationRef.current
          : 15000;

      timeoutId = setTimeout(() => {
        showEntry(index + 1);
      }, safeDuration);
    };

    showEntry(0);

    return () => {
      stopped = true;
      if (timeoutId !== null) {
        clearTimeout(timeoutId);
      }
    };
  }, [run]);

  return { currentEntry, entryIndex };
}

export default useMultipleEntrySlideExecution;
