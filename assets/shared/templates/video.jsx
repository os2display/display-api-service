import { useEffect, useRef, useLayoutEffect } from "react";
import {
  getAllMediaUrlsFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import "./video/video.scss";
import templateConfig from "./video.json";

// How long to wait for a usable duration before giving up on the video.
const metadataGuardMs = 30000;

// Flat margin added on top of the 10% duration overshoot guard.
const bufferingGuardMarginMs = 5000;

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
 * @param {number} props.run Run id. Changes each time the slide should run.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Video({ slide, content, run, slideDone, executionId }) {
  const videoUrls = getAllMediaUrlsFromField(slide.mediaData, content.video);
  const videoRef = useRef();
  const doneRef = useRef(false);
  const slideRef = useRef(slide);
  const slideDoneRef = useRef(slideDone);
  const { sound, mediaContain = true } = content;

  // Read when a guard timer or media event fires, not when the effect ran, so
  // they must come from refs. Same discipline as the slide-execution hooks.
  useLayoutEffect(() => {
    slideRef.current = slide;
    slideDoneRef.current = slideDone;
  });

  const finish = () => {
    if (!doneRef.current) {
      doneRef.current = true;
      slideDoneRef.current(slideRef.current);
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

    // Covers a source that neither loads nor errors. Stays armed until a
    // duration-based guard replaces it, so no path is left without a backstop.
    let loadGuardTimeout = setTimeout(finish, metadataGuardMs);

    // Some sources (fragmented WebM, streams) report an infinite duration at
    // loadedmetadata and only resolve it later, hence durationchange too.
    //
    // The guard is installed once, from the first usable duration. A source
    // whose duration keeps growing is therefore cut at that first value —
    // accepted deliberately, since re-arming would move the deadline along
    // with the stream and the guard would never fire, which is the playlist
    // lock it exists to prevent.
    const onDurationAvailable = () => {
      if (guardTimeout !== null) return;
      if (!Number.isFinite(video.duration) || video.duration <= 0) return;

      clearTimeout(loadGuardTimeout);
      loadGuardTimeout = null;

      // Allow 10% plus a flat margin for buffering delays — 10% alone is a
      // very short window on a short clip.
      const guardMs = video.duration * 1.1 * 1000 + bufferingGuardMarginMs;
      guardTimeout = setTimeout(finish, guardMs);
    };

    video.addEventListener("ended", finish);
    video.addEventListener("error", finish);
    video.addEventListener("loadedmetadata", onDurationAvailable);
    video.addEventListener("durationchange", onDurationAvailable);

    video.load();
    video.muted = !sound;

    const promise = video.play();

    if (promise !== undefined) {
      promise
        .then(() => {})
        .catch(() => {
          // Autoplay was rejected — expected whenever `sound` is on, since the
          // video is then unmuted. Offer controls and let the guards above
          // progress the slide rather than dropping it instantly.
          video.controls = true;
        });
    }

    return () => {
      video.removeEventListener("ended", finish);
      video.removeEventListener("error", finish);
      video.removeEventListener("loadedmetadata", onDurationAvailable);
      video.removeEventListener("durationchange", onDurationAvailable);
      if (loadGuardTimeout !== null) {
        clearTimeout(loadGuardTimeout);
      }
      if (guardTimeout !== null) {
        clearTimeout(guardTimeout);
      }

      // The element survives a run→falsy transition in previews; without this
      // it keeps playing, and an unmuted video keeps making noise.
      if (!video.paused) {
        video.pause();
      }
    };
  }, [run]);

  return (
    <>
      <div className="template-video">
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
