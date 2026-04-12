import { useEffect, useRef } from "react";
import BaseSlideExecution from "./base-slide-execution.js";

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
    const slideExecution = new BaseSlideExecution(
      slideRef.current,
      (s) => slideDoneRef.current(s),
    );

    if (run) {
      slideExecution.start(durationRef.current);
    }

    return function cleanup() {
      slideExecution.stop();
    };
  }, [run]);
}

export default useBaseSlideExecution;
