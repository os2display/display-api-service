import { useEffect } from "react";
import Vimeo from "@u-wave/react-vimeo";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import "./vimeo-player/vimeo-player.scss";
import templateConfig from "./vimeo-player.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <VimeoPlayer
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Vimeo Player component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function VimeoPlayer({ slide, content, run, slideDone, executionId }) {
  const { vimeoid, duration = 15000, mediaContain } = content;

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
      <div className="template-vimeo-player">
        <Vimeo
          video={vimeoid}
          responsive
          autoplay
          muted
          controls={false}
          paused={false}
          loop
          className={`vimeo-player${mediaContain ? " media-contain" : ""}`}
        />
      </div>
      <ThemeStyles id={executionId} css={slide?.themeData?.cssStyles} />
    </>
  );
}

function renderAdminForm(formStateObject, onChange, handleMedia, mediaData) {
  return (
    <></>
  );
}

export default { id, config, renderSlide, renderAdminForm };
