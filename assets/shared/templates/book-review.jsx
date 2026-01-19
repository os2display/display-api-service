import { useEffect } from "react";
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import "./book-review/book-review.scss";
import "../slide-utils/global-styles.css";
import templateConfig from "./book-review.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <BookReview
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
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
function BookReview({ slide, content, run, slideDone, executionId }) {
  const { authorText, bookText, duration = 15000 } = content;
  const sanitizedParsedBookText = bookText
    ? parse(DOMPurify.sanitize(bookText, {}))
    : "";

  const authorImageUrl = getFirstMediaUrlFromField(
    slide.mediaData,
    content.authorImage,
  );
  const bookImageUrl = getFirstMediaUrlFromField(
    slide.mediaData,
    content.bookImage,
  );

  const authorStyle = authorImageUrl
    ? { backgroundImage: `url("${authorImageUrl}")` }
    : "";
  const bookStyle = bookImageUrl
    ? { backgroundImage: `url("${bookImageUrl}")` }
    : "";

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
      <div className="template-book-review">
        <div className="text-area">
          <div>{sanitizedParsedBookText}</div>
        </div>
        <div className="author-area">
          {authorStyle && <div className="author-image" style={authorStyle} />}
          <div className="author">{authorText}</div>
        </div>
        <div className="book-image-area">
          {bookStyle && (
            <>
              <div className="image-blurry-background" style={bookStyle} />
              <div className="book-image">
                <img src={bookImageUrl} alt="book" />
              </div>
            </>
          )}
        </div>
      </div>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

function renderAdminForm(formStateObject, onChange, handleMedia, mediaData) {
  return (
    <></>
  );
}

export default { id, config, renderSlide, renderAdminForm };
