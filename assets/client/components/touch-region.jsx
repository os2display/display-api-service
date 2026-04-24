import { useEffect, useState, createRef } from "react";
import { createGridArea } from "../../shared/grid-generator/grid-generator";
import ErrorBoundary from "./error-boundary.jsx";
import idFromPath from "../util/id-from-path";
import IconClose from "../assets/icon-close.svg";
import IconPointer from "../assets/icon-pointer.svg";
import Slide from "./slide.jsx";
import { useClientState } from "../client-state-context.jsx";
import "./touch-region.scss";

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
function TouchRegion({ region }) {
  const [slides, setSlides] = useState(null);
  const [currentSlide, setCurrentSlide] = useState(null);
  const [displayClose, setDisplayClose] = useState(false);
  const [nodeRefs, setNodeRefs] = useState({});
  const [runId, setRunId] = useState(null);

  const { regionSlides, callbacks } = useClientState();
  const rootStyle = {};
  const regionId = idFromPath(region["@id"]);
  const incomingSlides = regionSlides[regionId];

  rootStyle.gridArea = createGridArea(region.gridArea);

  /**
   * The slide is done executing.
   *
   * @param {object} slide - The slide.
   */
  const slideDone = (slide) => {
    setDisplayClose(false);
    setCurrentSlide(null);

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

    setSlides([...incomingSlides].filter((slide) => !slide.invalid));
  }, [incomingSlides]);

  // Notify lifecycle on mount/unmount.
  useEffect(() => {
    callbacks.current.onRegionReady(regionId);

    return function cleanup() {
      callbacks.current.onRegionRemoved(regionId);
    };
  }, [regionId]);

  // Make sure current slide is set.
  useEffect(() => {
    if (!slides) return;

    // Add or remove refs.
    setNodeRefs((prevNodeRefs) =>
      slides.reduce((res, element) => {
        res[element.executionId] =
          prevNodeRefs[element.executionId] || createRef();
        return res;
      }, {}),
    );
  }, [slides]);

  const startSlide = (slide) => {
    setDisplayClose(true);
    setCurrentSlide(slide);
    setRunId(new Date().toISOString());
  };

  return (
    <div className="touch-region" style={rootStyle} id={regionId}>
      <ErrorBoundary resetKey={currentSlide?.executionId}>
        <>
          {currentSlide !== null && (
            <div className="touch-region-container">
              <div className="touch-region-content">
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
              </div>
              <div className="touch-region-footer">
                <div className="touch-buttons-container">
                  {displayClose && (
                    <div
                      className="touch-button-close"
                      onClick={() => slideDone(currentSlide)}
                      onKeyDown={(e) => { if (e.key === "Enter" || e.key === " ") slideDone(currentSlide); }}
                      role="button"
                      tabIndex={0}
                    >
                      <div className="touch-button-icon">
                        <IconClose />
                      </div>
                      <div className="touch-button-text">LUK</div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
          <div className="touch-buttons-container">
            {slides &&
              slides.map((slide) => (
                <div
                  className="touch-button"
                  key={`button-${slide.executionId}`}
                  onClick={() => startSlide(slide)}
                  onKeyDown={(e) => { if (e.key === "Enter" || e.key === " ") startSlide(slide); }}
                  role="button"
                  tabIndex={0}
                >
                  <div className="touch-button-icon">
                    <IconPointer />
                  </div>
                  <div className="touch-button-text">
                    {slide.content?.touchRegionButtonText ?? slide.title}
                  </div>
                </div>
              ))}
          </div>
        </>
      </ErrorBoundary>
    </div>
  );
}

export default TouchRegion;
