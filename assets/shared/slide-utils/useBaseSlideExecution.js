import { useEffect, useLayoutEffect, useRef } from "react";
import clampDuration from "./duration.js";

/**
 * Hook to manage slide execution lifecycle.
 *
 * Keeps [run] as the only dependency that starts the timer, so a re-render with
 * new props cannot restart a slide that is already playing. `slide` and
 * `slideDone` are read through refs because they are read when the timer
 * *fires*, long after the effect ran; `duration` is read when the timer is
 * *set*, so the effect closure already holds the current value.
 *
 * @param {object} options
 * @param {object} options.slide The slide object.
 * @param {number|null} options.run Run token: falsy means "do not run", and a
 *   new truthy value restarts the slide without a remount.
 * @param {Function} options.slideDone Callback when slide finishes.
 * @param {number} options.duration Duration in ms. Invalid or missing falls
 *   back to DEFAULT_DURATION.
 */
function useBaseSlideExecution({ slide, run, slideDone, duration }) {
  const slideRef = useRef(slide);
  const slideDoneRef = useRef(slideDone);

  // Layout effects run before passive effects on the same commit, so the refs
  // are current when the timer effect below reads them synchronously.
  useLayoutEffect(() => {
    slideRef.current = slide;
    slideDoneRef.current = slideDone;
  });

  useEffect(() => {
    if (!run) return;

    const timeoutId = setTimeout(() => {
      slideDoneRef.current(slideRef.current);
    }, clampDuration(duration));

    return () => clearTimeout(timeoutId);
    // `duration` is deliberately not a dependency: changing it mid-run must not
    // restart a slide that is already playing.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [run]);
}

export default useBaseSlideExecution;
