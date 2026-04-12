import { useState, useEffect, useRef } from "react";
import dayjs from "dayjs";
import localeDa from "dayjs/locale/da";
import relativeTime from "dayjs/plugin/relativeTime";
import localizedFormat from "dayjs/plugin/localizedFormat";
import QRCode from "qrcode";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import useMultipleEntrySlideExecution from "../slide-utils/useMultipleEntrySlideExecution.js";
import "../slide-utils/global-styles.css";
import "./news-feed/news-feed.scss";
import templateConfig from "./news-feed.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <NewsFeed
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * News feed slide.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function NewsFeed({ slide, content, run, slideDone, executionId }) {
  dayjs.extend(localizedFormat);
  dayjs.extend(relativeTime);

  const [qr, setQr] = useState(null);
  const fallbackRef = useRef(null);

  const { feedData = [], mediaData = {} } = slide;
  const {
    entryDuration = 10,
    mediaContain = false,
    readMore,
    fallbackImage,
  } = content;

  const fallbackImageUrl = getFirstMediaUrlFromField(mediaData, fallbackImage);
  const feedEntries = feedData?.entries ?? [];

  const { currentEntry: currentPost } = useMultipleEntrySlideExecution({
    entries: feedEntries,
    run,
    slide,
    slideDone,
    entryDuration: entryDuration * 1000,
  });

  // Generate QR code for current post link.
  useEffect(() => {
    if (!currentPost?.link) {
      setQr(null);
      return;
    }
    QRCode.toDataURL(currentPost.link, {
      margin: 0,
      color: { dark: "#000000", light: "#ffffff00" },
    }).then((data) => setQr(data));
  }, [currentPost]);

  // If no content, wait 5 seconds and continue to next slide.
  useEffect(() => {
    if (run && feedEntries.length === 0) {
      fallbackRef.current = setTimeout(() => slideDone(slide), 5000);
    }

    return () => {
      if (fallbackRef.current) {
        clearTimeout(fallbackRef.current);
      }
    };
  }, [run]);

  const getImageUrl = (post) => {
    let imageUrl = fallbackImageUrl ?? null;

    if (post?.medias instanceof Array) {
      const medias = [...post?.medias];

      if (medias?.length > 0) {
        const first = medias.pop();

        if (first?.url) {
          imageUrl = first.url;
        }
      }
    }

    return imageUrl;
  };

  const imageUrl = currentPost ? getImageUrl(currentPost) : null;

  return (
    <>
      {currentPost && (
        <div className="template-news-feed">
          <div
            className={`media-section ${mediaContain ? "media-contain" : ""}`}
            style={{
              backgroundImage: imageUrl ? `url("${imageUrl}")` : "",
            }}
          />
          <div className="text-section">
            <h1 className="title">{currentPost.title}</h1>
            <div className="author">
              {currentPost.lastModified
                ? dayjs(currentPost.lastModified).locale(localeDa).format("ll")
                : ""}
              {currentPost.lastModified && currentPost?.author?.name && " ▪ "}
              {currentPost?.author?.name}
            </div>
            <div className="description">{currentPost.summary}</div>
            <div className="description-fade" />
          </div>
          <div className="extra-section">
            {qr && <img src={qr} alt="QR code link" className="qr" />}
            {currentPost.link && (
              <>
                <div className="read-more">
                  {readMore || "Læs hele nyheden"}
                </div>
                <div className="link">{currentPost.link}</div>
              </>
            )}
          </div>
        </div>
      )}

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

export default { id, config, renderSlide };
