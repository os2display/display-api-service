import { useEffect, useRef } from "react";
import {
  getAllMediaUrlsFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import "./video/video.scss";
import templateConfig from "./video.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Video
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
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
  const doneRef = useRef(false);
  const { sound, mediaContain } = content;

  const finish = () => {
    if (!doneRef.current) {
      doneRef.current = true;
      slideDone(slide);
    }
  };

  useEffect(() => {
    if (!run) return;

    doneRef.current = false;

    if (videoUrls.length === 0) {
      finish();
      return;
    }

    const video = videoRef.current;
    if (!video) {
      finish();
      return;
    }

    let guardTimeout = null;

    const onLoadedMetadata = () => {
      if (Number.isFinite(video.duration) && video.duration > 0) {
        // Allow 10% extra time for buffering delays.
        const guardMs = video.duration * 1.1 * 1000;
        guardTimeout = setTimeout(finish, guardMs);
      }
    };

    video.addEventListener("ended", finish);
    video.addEventListener("error", finish);
    video.addEventListener("loadedmetadata", onLoadedMetadata);

    video.load();
    video.muted = !sound;

    const promise = video.play();

    if (promise !== undefined) {
      promise
        .then(() => {})
        .catch(() => {
          video.controls = true;
          finish();
        });
    }

    return () => {
      video.removeEventListener("ended", finish);
      video.removeEventListener("error", finish);
      video.removeEventListener("loadedmetadata", onLoadedMetadata);
      if (guardTimeout !== null) {
        clearTimeout(guardTimeout);
      }
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
