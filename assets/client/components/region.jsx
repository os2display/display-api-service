import { useEffect, useState, createRef } from "react";
import { createGridArea } from "../../shared/grid-generator/grid-generator";
import { TransitionGroup, CSSTransition } from "react-transition-group";
import ErrorBoundary from "./error-boundary.jsx";
import idFromPath from "../util/id-from-path";
import isRenderableSlide from "../util/is-renderable-slide";
import logger from "../logger/logger";
import Slide, { MIN_SLIDE_DWELL_MS } from "./slide.jsx";
import nextRunId from "../../shared/slide-utils/next-run-id.js";
import "./region.scss";

/**
 * Region component.
 *
 * @param {object} props
 *   Props.
 * @param {object} props.region
 *   The region content.
 * @returns {object}
 *   The component.
 */
function Region({ region }) {
  const [slides, setSlides] = useState(null);
  const [currentSlide, setCurrentSlide] = useState(null);
  const [newSlides, setNewSlides] = useState(null);
  const [nodeRefs, setNodeRefs] = useState({});
  const [runId, setRunId] = useState(null);

  const rootStyle = {};
  const regionId = idFromPath(region["@id"]);

  rootStyle.gridArea = createGridArea(region.gridArea);

  /**
   * Find the slide after the slide with the fromId.
   *
   * @param {number} fromId
   *   The id from which the next slide is determined.
   * @returns {object}
   *   The slide.
   */
  function findNextSlide(fromId) {
    const slideIndex = slides.findIndex(
      (slideElement) => slideElement.executionId === fromId,
    );

    const nextIndex = (slideIndex + 1) % slides.length;

    return {
      nextSlide: slides[nextIndex],
      nextIndex,
    };
  }

  /**
   * The slide is done executing.
   *
   * @param {object} slide - The slide.
   */
  const slideDone = (slide) => {
    const nextSlideAndIndex = findNextSlide(slide.executionId);

    if (nextSlideAndIndex.nextIndex === 0 && Array.isArray(newSlides)) {
      const nextSlides = [...newSlides];
      setSlides(nextSlides);
      setNewSlides(null);
      setCurrentSlide(nextSlides[0]);
    } else {
      setCurrentSlide(nextSlideAndIndex.nextSlide);
    }

    setRunId(nextRunId);

    logger.info(`Slide done with executionId: ${slide?.executionId}`);

    // Emit slideDone event.
    const slideDoneEvent = new CustomEvent("slideDone", {
      detail: {
        regionId,
        executionId: slide.executionId,
      },
    });
    document.dispatchEvent(slideDoneEvent);
  };

  /**
   * The slide has encountered an error.
   *
   * @param {object} slideWithError - The slide
   */
  const slideError = (slideWithError) => {
    // Set error timestamp to force reload.
    const slide = slides.find(
      (slideElement) => slideElement.executionId === slideWithError.executionId,
    );
    slide.errorTimestamp = new Date().getTime();
    slideDone(slideWithError);
  };

  /**
   * Handle region content event.
   *
   * @param {CustomEvent} event
   *   The event. The data is contained in detail.
   */
  function regionContentListener(event) {
    const receivedSlides = [...event.detail.slides];

    // Filter out invalid slides. Shared with ScheduleService.checkForEmptyContent
    // so the two cannot disagree about what counts as content.
    setNewSlides(receivedSlides.filter(isRenderableSlide));
  }

  // Setup event listener for region content.
  useEffect(() => {
    logger.info(`Mounting region ${regionId}`);

    document.addEventListener(
      `regionContent-${regionId}`,
      regionContentListener,
    );

    return function cleanup() {
      logger.info(`Unmounting region ${regionId}`);

      // Emit event that region has been removed.
      const event = new CustomEvent("regionRemoved", {
        detail: {
          id: regionId,
        },
      });
      document.dispatchEvent(event);

      // Cleanup event listener.
      document.removeEventListener(
        `regionContent-${regionId}`,
        regionContentListener,
      );
    };
  }, []);

  // Notify that region is ready. Mount only: content is pushed by ScheduleService
  // from here on, and asking again whenever the region prop changes identity was
  // both redundant and unreliable - a pull that served the layout from cache
  // hands back the same object, so the effect never ran (#507).
  useEffect(() => {
    const event = new CustomEvent("regionReady", {
      detail: {
        id: regionId,
      },
    });
    document.dispatchEvent(event);
  }, []);

  // Start the progress if no slide is currently playing.
  useEffect(() => {
    if (newSlides !== null && !currentSlide) {
      setSlides(newSlides);
    }
  }, [newSlides]);

  // Make sure current slide is set.
  useEffect(() => {
    if (!slides) return;

    if (!currentSlide) {
      if (slides.length > 0) {
        setCurrentSlide(slides[0]);
        setRunId(nextRunId);
      }
    }

    // Add or remove refs.
    setNodeRefs((prevNodeRefs) =>
      slides.reduce((res, element) => {
        res[element.executionId] =
          prevNodeRefs[element.executionId] || createRef();
        return res;
      }, {}),
    );
  }, [slides]);

  return (
    <div className="region" style={rootStyle} id={regionId}>
      <ErrorBoundary>
        <>
          {/* The cross-fade below and the dwell floor are deliberately the same
          length: a slide cannot advance the region before the floor, so a
          transition of that length always completes. region.scss holds the
          matching opacity transition. */}
          <TransitionGroup component={null}>
            {currentSlide && (
              <CSSTransition
                key={currentSlide.executionId}
                timeout={MIN_SLIDE_DWELL_MS}
                classNames="slide"
                nodeRef={nodeRefs[currentSlide.executionId]}
              >
                <Slide
                  slide={currentSlide}
                  id={currentSlide.executionId}
                  run={runId}
                  slideDone={slideDone}
                  slideError={slideError}
                  errorTimestamp={currentSlide.errorTimestamp}
                  key={currentSlide.executionId}
                  forwardRef={nodeRefs[currentSlide.executionId]}
                />
              </CSSTransition>
            )}
          </TransitionGroup>
        </>
      </ErrorBoundary>
    </div>
  );
}

export default Region;
