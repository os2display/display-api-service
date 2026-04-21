import { useEffect, useState, createRef, useRef } from "react";
import { createGridArea } from "../../shared/grid-generator/grid-generator";
import { TransitionGroup, CSSTransition } from "react-transition-group";
import ErrorBoundary from "./error-boundary.jsx";
import idFromPath from "../util/id-from-path";
import logger from "../logger";
import Slide from "./slide.jsx";
import constants from "../util/constants";
import { useClientState } from "../client-state-context.jsx";
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

  // Refs to avoid stale closures in slideDone — templates capture slideDone
  // in a useEffect([run]) that won't re-run when slides/newSlides change.
  const slidesRef = useRef(null);
  const newSlidesRef = useRef(null);
  slidesRef.current = slides;
  newSlidesRef.current = newSlides;

  const { regionSlides, callbacks } = useClientState();
  const rootStyle = {};
  const regionId = idFromPath(region["@id"]);
  const incomingSlides = regionSlides[regionId];

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
    const currentSlides = slidesRef.current;

    if (!currentSlides || currentSlides.length === 0) {
      return { nextSlide: null, nextIndex: 0 };
    }

    const slideIndex = currentSlides.findIndex(
      (slideElement) => slideElement.executionId === fromId,
    );

    const nextIndex = (slideIndex + 1) % currentSlides.length;

    return {
      nextSlide: currentSlides[nextIndex],
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
    const latestNewSlides = newSlidesRef.current;

    if (nextSlideAndIndex.nextIndex === 0 && Array.isArray(latestNewSlides)) {
      const nextSlides = [...latestNewSlides];
      setSlides(nextSlides);
      setNewSlides(null);
      setCurrentSlide(nextSlides.length > 0 ? nextSlides[0] : null);
    } else {
      setCurrentSlide(nextSlideAndIndex.nextSlide);
    }

    setRunId(new Date().toISOString());

    logger.info(`Slide done with executionId: ${slide?.executionId}`);
  };

  /**
   * The slide has encountered an error.
   *
   * @param {object} slideWithError - The slide
   */
  const slideError = (slideWithError) => {
    // Set error timestamp to force reload.
    setSlides((prev) =>
      prev.map((s) =>
        s.executionId === slideWithError.executionId
          ? { ...s, errorTimestamp: new Date().getTime() }
          : s,
      ),
    );
    slideDone(slideWithError);
  };

  // Receive region slides from context.
  useEffect(() => {
    if (!incomingSlides) return;

    const receivedSlides = [...incomingSlides];
    setNewSlides(receivedSlides.filter((slide) => !slide.invalid));
  }, [incomingSlides]);

  // Notify lifecycle on mount/unmount.
  useEffect(() => {
    logger.info(`Mounting region ${regionId}`);
    callbacks.current.onRegionReady(regionId);

    return function cleanup() {
      logger.info(`Unmounting region ${regionId}`);
      callbacks.current.onRegionRemoved(regionId);
    };
  }, [regionId]);

  // Start the progress if no slide is currently playing.
  useEffect(() => {
    if (newSlides !== null && !currentSlide) {
      setSlides(newSlides);
      setNewSlides(null);
    }
  }, [newSlides, currentSlide]);

  // Make sure current slide is set.
  useEffect(() => {
    if (!slides) return;

    if (!currentSlide) {
      if (slides.length > 0) {
        setCurrentSlide(slides[0]);
        setRunId(new Date().toISOString());
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
  }, [slides, currentSlide]);

  return (
    <div className="region" style={rootStyle} id={regionId}>
      <ErrorBoundary resetKey={currentSlide?.executionId}>
        <>
          <TransitionGroup component={null}>
            {currentSlide && (
              <CSSTransition
                key={currentSlide.executionId}
                timeout={constants.SLIDE_TRANSITION_TIMEOUT}
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
