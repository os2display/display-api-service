import React, { useEffect } from "react";
import templateConfig from "./custom-template-example.json";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return <CustomTemplateExample
    slide={slide}
    run={run}
    slideDone={slideDone}
    content={slide.content}
    executionId={slide.executionId}
  />;
}

/**
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function CustomTemplateExample({ slide, content, run, slideDone, executionId }) {
  const { duration = 15000 } = content;
  const { title = "Default title" } = content;

  const slideExecution = new BaseSlideExecution(slide, slideDone);

  useEffect(() => {
    if (run) {
      slideExecution.start(duration);
    }

    return function cleanup() {
      slideExecution.stop();
    };
  }, [run]);

  return (<>
    <div className="custom-template-example">
      <h1 className="title">{title}</h1>
    </div>

    <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
  </>);
}

export default { id, config, renderSlide };
