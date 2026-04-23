import { useEffect, useRef } from "react";

/**
 * Hook to manage slide execution lifecycle.
 *
 * Uses refs for slide, slideDone, and duration to avoid stale closures
 * while keeping [run] as the only dependency that triggers the timer.
 *
 * @param {object} options
 * @param {object} options.slide The slide object.
 * @param {boolean} options.run Whether the slide should run.
 * @param {Function} options.slideDone Callback when slide finishes.
 * @param {number} options.duration Duration in ms.
 */
function useBaseSlideExecution({ slide, run, slideDone, duration }) {
  const slideRef = useRef(slide);
  const slideDoneRef = useRef(slideDone);
  const durationRef = useRef(duration);

  slideRef.current = slide;
  slideDoneRef.current = slideDone;
  durationRef.current = duration;

  useEffect(() => {
    if (!run) return;

    const safeDuration =
      Number.isFinite(durationRef.current) && durationRef.current > 0
        ? durationRef.current
        : 15000;

    const timeoutId = setTimeout(() => {
      slideDoneRef.current(slideRef.current);
    }, safeDuration);

    return () => clearTimeout(timeoutId);
  }, [run]);
}

export default useBaseSlideExecution;
