import React, { useEffect } from "react";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import templateConfig from './iframe.json';

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return <IFrame
    slide={slide}
    run={run}
    slideDone={slideDone}
    content={slide.content}
    executionId={slide.executionId}
  />
}

/**
 * IFrame component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function IFrame({ slide, content, run, slideDone, executionId }) {
  const { source, duration = 15000 } = content;

  /** Setup slide run function. */
  const slideExecution = new BaseSlideExecution(slide, slideDone);
  useEffect(() => {
    if (run) {
      slideExecution.start(duration);
    }

    return function cleanup() {
      slideExecution.stop();
    };
  }, [run]);

  return (
    <>
      <iframe
        title="iframe title"
        sandbox="allow-same-origin allow-scripts"
        frameBorder="0"
        scrolling="no"
        src={source}
        width="100%"
        height="100%"
      />
      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

export default { id, config, renderSlide };
