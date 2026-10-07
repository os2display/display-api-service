import { useCallback, useEffect, useLayoutEffect, useRef } from "react";
import ErrorBoundary from "./error-boundary.jsx";
import logger from "../logger/logger";
import { renderSlide } from "../../shared/slide-utils/templates.js";
import "./slide.scss";

// A slide must be on screen this long before the region acts on slideDone().
// Templates may finish while mounting -- a video slide with no playable media
// calls slideDone() synchronously from its own effect -- and a region replays a
// slide by handing it a new run id, so an unguarded advance becomes an
// unbounded effect -> slideDone -> new run id -> effect loop. The region drives
// its cross-fade off this same value, so a transition can always finish before
// the next advance -- keep region.scss's opacity transition in step.
export const MIN_SLIDE_DWELL_MS = 1000;

/**
 * Render the slide's template.
 *
 * A component rather than a call in Slide's own render: renderSlide throws when
 * the slide names a template this build does not bundle, and an argument is
 * evaluated before ErrorBoundary mounts. The throw escaped the boundary meant
 * to contain it and hit the region's instead, which has no handler and never
 * resets - so one unrenderable slide replaced its whole region with the error
 * fallback until the client was reloaded. Thrown from inside the boundary's
 * subtree it costs that one slide, which is then moved on from.
 *
 * @param {object} props - Props.
 * @param {object} props.slide - The slide data.
 * @param {number} props.run - Run id. Changes each time the slide should run.
 * @param {Function} props.slideDone - The function to call when the slide is done running.
 * @returns {object} - The rendered template.
 */
function SlideTemplate({ slide, run, slideDone }) {
  return renderSlide(slide, run, slideDone);
}

/**
 * Slide component.
 *
 * @param {object} props - Props.
 * @param {object} props.slide - The slide data.
 * @param {string} props.id - The unique slide id.
 * @param {number} props.run - Run id. Changes each time the slide should run.
 * @param {Function} props.slideDone - The function to call when the slide is done running.
 * @param {Function} props.slideError - Callback when slide encountered an error.
 * @param {object} props.forwardRef - Ref to the slide root element. Used as
 *   nodeRef by the region's CSSTransition.
 * @returns {object} - The component.
 */
function Slide({ slide, id, run, slideDone, slideError, forwardRef }) {
  const slideDoneRef = useRef(slideDone);
  const pendingDoneRef = useRef(null);
  const doneForRunRef = useRef(null);
  const runStartRef = useRef({ run: null, at: 0 });

  // When the current run started, recorded during render rather than in a
  // layout effect. React runs a child's effects before its parent's, but only
  // within a phase: a template finishing from its own *layout* effect signals
  // before a layout effect here could stamp the run start, which would leave
  // this at its {run: null, at: 0} initial value and make the guard below
  // misread the run -- measuring the dwell from 0 lets the advance through at
  // once, and a null run reads as "already accepted" and swallows it. Only a
  // render-phase write is ahead of every child effect.
  //
  // The in-tree templates do not make that obvious: video.jsx finishes from a
  // passive effect, which a layout effect here would beat, so reasoning from
  // the templates alone makes a layout effect look sufficient. It is not --
  // see region-layout-effect-finish.test.jsx.
  //
  // performance.now() rather than Date.now() because it is monotonic -- a wall
  // clock step on a screen that has been up for weeks must not make a slide
  // advance instantly or hang. The timer coarsening that rules a clock out for
  // run ids does not matter to a one second floor.
  if (runStartRef.current.run !== run) {
    runStartRef.current = { run, at: performance.now() };
  }

  // Read when the dwell timer fires, long after the effect ran, so it has to
  // come from a ref. Same discipline as the slide execution hooks.
  useLayoutEffect(() => {
    slideDoneRef.current = slideDone;
  });

  useEffect(() => {
    // A deferred advance belongs to the run that scheduled it. Letting it
    // survive into the next run would advance the region twice.
    return () => {
      if (pendingDoneRef.current !== null) {
        clearTimeout(pendingDoneRef.current);
        pendingDoneRef.current = null;
      }
    };
  }, [run]);

  /**
   * Report the slide done, no sooner than MIN_SLIDE_DWELL_MS into the run.
   *
   * @param {object} doneSlide - The slide that finished.
   */
  const slideDoneAfterMinimumDwell = useCallback((doneSlide) => {
    // Not running: a falsy run means the region has not started this slide, so
    // there is no run to finish. Checked before the per-run guard below, whose
    // "nothing accepted yet" value is also null.
    if (!runStartRef.current.run) {
      return;
    }

    // One advance per run: a template that signals more than once in a single
    // run still moves the region on only once, whether the extra signals arrive
    // while a deferred advance is pending or after the floor has passed. Two
    // late signals in one tick would otherwise both read the pre-update run
    // start and call slideDone() twice, which React batches into two run id
    // increments -- a genuine double advance.
    //
    // Read through runStartRef rather than the `run` prop: this callback has no
    // dependencies, so it closes over the run it was first created for. The
    // guard clears itself, since the render-phase write above moves
    // runStartRef.current.run on as soon as the region starts the next run.
    if (doneForRunRef.current === runStartRef.current.run) {
      return;
    }

    doneForRunRef.current = runStartRef.current.run;

    const remaining =
      MIN_SLIDE_DWELL_MS - (performance.now() - runStartRef.current.at);

    if (remaining <= 0) {
      slideDoneRef.current(doneSlide);
      return;
    }

    pendingDoneRef.current = setTimeout(() => {
      pendingDoneRef.current = null;
      slideDoneRef.current(doneSlide);
    }, remaining);
  }, []);

  /**
   * Handle errors in ErrorBoundary.
   *
   * Call slideDone after a timeout to ensure progression.
   */
  const handleError = () => {
    logger.warn("Slide error boundary triggered.");

    setTimeout(() => {
      slideError(slide);
    }, 5000);
  };

  return (
    <div
      ref={forwardRef}
      id={id}
      className="slide"
      data-run={run}
      data-execution-id={slide.executionId}
    >
      <ErrorBoundary errorHandler={handleError}>
        <SlideTemplate
          slide={slide}
          run={run}
          slideDone={slideDoneAfterMinimumDwell}
        />
      </ErrorBoundary>
    </div>
  );
}

export default Slide;
