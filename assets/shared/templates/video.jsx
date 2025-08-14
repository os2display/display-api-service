import React, { useEffect, useRef } from "react";
import { getAllMediaUrlsFromField, ThemeStyles } from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import "./video/video.scss";
import templateConfig from './video.json';

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return <Video
    slide={slide}
    run={run}
    slideDone={slideDone}
    content={slide.content}
    executionId={slide.executionId}
  />
}

/**
 * Video component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Video({ slide, content, run, slideDone, executionId }) {
  const videoUrls = getAllMediaUrlsFromField(slide.mediaData, content.video);
  const videoRef = useRef();
  const { sound, mediaContain } = content;

  const onEnded = () => {
    slideDone(slide);
  };

  const onError = () => {
    slideDone(slide);
  };

  useEffect(() => {
    if (run) {
      videoRef?.current?.load();
      videoRef?.current?.addEventListener("ended", onEnded);
      videoRef?.current?.addEventListener("error", onError);
      videoRef.current.muted = true;

      if (sound) {
        videoRef.current.muted = false;
      }

      const promise = videoRef.current.play();

      if (promise !== undefined) {
        promise
          .then(() => {})
          .catch(() => {
            if (videoRef?.current) {
              videoRef.current.controls = true;
            }
          });
      }
    }

    return () => {
      videoRef?.current?.removeEventListener("ended", onEnded);
      videoRef?.current?.removeEventListener("error", onError);
    };
  }, [run]);

  return (
    <>
      <div className="template-video video-container">
        <video
          width="100%"
          height="100%"
          ref={videoRef}
          muted={!sound}
          className={mediaContain ? "media-contain" : ""}
        >
          {videoUrls.map((url) => (
            <source key={url} src={url} />
          ))}
          <track kind="captions" />
        </video>
      </div>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

export default { id, config, renderSlide };
