import { useState, useEffect } from "react";
import dayjs from "dayjs";
import localeDa from "dayjs/locale/da";
import relativeTime from "dayjs/plugin/relativeTime";
import localizedFormat from "dayjs/plugin/localizedFormat";
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import Shape from "./instagram-feed/shape.svg";
import InstagramLogo from "./instagram-feed/instagram-logo.svg";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";
import useMultipleEntrySlideExecution from "../slide-utils/useMultipleEntrySlideExecution.js";
import "../slide-utils/global-styles.css";
import "./instagram-feed/instagram-feed.scss";
import templateConfig from "./instagram-feed.json";
import { FormattedMessage, IntlProvider } from "react-intl";
import da from "./instagram-feed/lang/da.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <InstagramFeed
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Sparkle component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {number} props.run Run id. Changes each time the slide should run.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function InstagramFeed({ slide, content, run, slideDone, executionId }) {
  dayjs.extend(localizedFormat);
  dayjs.extend(relativeTime);

  const [translations] = useState(da);

  // Animation
  const [show, setShow] = useState(true);
  const animationDuration = 1500;

  const { feedData = [] } = slide;
  const { hashtagText, orientation, imageWidth = null, mediaContain } = content;

  // @TODO: should duration depend on number of instagram posts to show?
  let { entryDuration: duration } = content;
  duration = (duration || 15) * 1000; // Add a default

  const { maxEntries = 5 } = content;

  const maxEntriesToShow = Number.isInteger(maxEntries) ? maxEntries : 5;
  const feedEntries = feedData?.slice(0, maxEntriesToShow) ?? [];

  const { currentEntry: currentPost, entryDuration } =
    useMultipleEntrySlideExecution({
      entries: feedEntries,
      run,
      slide,
      slideDone,
      entryDuration: duration,
      emptyEntriesDuration: 1000,
    });

  // Trigger fade-out animation before entry changes.
  useEffect(() => {
    if (!currentPost) return;

    setShow(true);
    const animationTimer = setTimeout(
      () => {
        setShow(false);
        // See the note in poster.jsx: derived from the hook's clamped duration.
      },
      Math.max(0, entryDuration - animationDuration),
    );

    return () => clearTimeout(animationTimer);
  }, [currentPost]);

  // If no content, wait 1 second and continue to next slide.
  const getSanitizedMarkup = (textMarkup) => {
    return parse(DOMPurify.sanitize(textMarkup, {}));
  };

  const rootStyle = {};

  if (imageWidth !== null) {
    rootStyle["--percentage-wide"] = `${imageWidth}%`;
    rootStyle["--percentage-narrow"] = `${100 - imageWidth}%`;
  }

  return (
    <IntlProvider messages={translations} locale="da" defaultLocale="da">
      <div
        style={rootStyle}
        className={`template-instagram-feed ${orientation} ${
          show ? "show" : "hide"
        }`}
      >
        {currentPost && (
          <>
            <div className="media-section">
              {!currentPost.videoUrl && (
                <div
                  className={`image${mediaContain ? " media-contain" : ""}`}
                  style={{
                    backgroundImage: `url("${currentPost.mediaUrl}")`,
                    ...(show
                      ? { animation: `fade-in ${animationDuration}ms` }
                      : { animation: `fade-out ${animationDuration}ms` }),
                  }}
                />
              )}
              {currentPost.videoUrl && (
                <div className="video-container">
                  <video
                    muted="muted"
                    autoPlay
                    loop
                    src={currentPost.videoUrl}
                    className={mediaContain ? "media-contain" : ""}
                  >
                    <track kind="captions" />
                  </video>
                </div>
              )}
            </div>
            <div className="author-section">
              <h1 className="author">{currentPost.username}</h1>
              <div className="date">
                {dayjs(currentPost.createdTime).locale(localeDa).fromNow()}
              </div>
              <div className="description">
                {getSanitizedMarkup(currentPost.textMarkup)}
              </div>
            </div>
          </>
        )}
        {!currentPost && (
          <div className="no-content">
            <FormattedMessage
              id="no-content"
              defaultMessage="Ingen nye opslag"
            />
          </div>
        )}
        <div className="shape">
          <Shape />
        </div>
        <div className="brand">
          <InstagramLogo className="brand-icon" />
          <span className="brand-tag">{hashtagText}</span>
        </div>
      </div>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </IntlProvider>
  );
}

export default { id, config, renderSlide };
