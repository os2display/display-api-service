import { createRef, useEffect, useRef, useState } from "react";
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import { CSSTransition, TransitionGroup } from "react-transition-group";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import {
  getAllMediaUrlsFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import "../slide-utils/global-styles.css";
import "./image-text/image-text.scss";
import imageTextConfig from "./image-text.json";

function id() {
  return imageTextConfig.id;
}

function config() {
  return imageTextConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <ImageText
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
function ImageText({ slide, content, run, slideDone, executionId }) {
  const imageTimeoutRef = useRef();
  const imagesRef = useRef([]);
  const durationRef = useRef();
  const [images, setImages] = useState([]);
  imagesRef.current = images;
  const [currentImage, setCurrentImage] = useState();
  const logo = slide?.theme?.logo;
  const {
    showLogo,
    logoSize,
    logoPosition,
    logoMargin,
    mediaContain,
    disableImageFade,
  } = content;

  const logoUrl = showLogo && logo?.assets?.uri ? logo.assets.uri : "";

  const logoClasses = ["logo"];

  if (logoMargin) {
    logoClasses.push("logo-margin");
  }
  if (logoSize) {
    logoClasses.push(logoSize);
  }
  if (logoPosition) {
    logoClasses.push(logoPosition);
  }

  // Styling from content
  const {
    separator,
    boxAlign,
    reversed,
    boxMargin,
    halfSize,
    fontSize,
    shadow,
  } = content;

  let boxClasses = "box";

  // Styling objects
  const imageTextStyle = {};

  // Content from content
  const { title, text, textColor, boxColor, duration = 15000 } = content;
  durationRef.current = duration;

  const sanitizedText = DOMPurify.sanitize(text);

  // Display separator depends on whether the slide is reversed.
  const displaySeparator = separator && !reversed;

  // Set background image.
  if (images.length === 0) {
    boxClasses = `${boxClasses} full-screen`;
  }

  // Set box colors.
  if (boxColor) {
    imageTextStyle.backgroundColor = boxColor;
  }
  if (textColor) {
    imageTextStyle.color = textColor;
  }

  const rootClasses = ["template-image-text", fontSize];

  // Position text-box.
  if (boxAlign === "left" || boxAlign === "right") {
    rootClasses.push("column");
  }
  if (boxAlign === "bottom" || boxAlign === "right") {
    rootClasses.push("flex-end");
  }
  if (reversed) {
    rootClasses.push("reversed");
  }
  if (boxMargin || reversed) {
    rootClasses.push("box-margin");
  }
  if (halfSize && !reversed) {
    rootClasses.push("half-size");
  }
  if (separator && !reversed) {
    rootClasses.push("animated-header");
  }
  if (shadow) {
    rootClasses.push("shadow");
  }

  const changeImage = (newIndex) => {
    const currentImages = imagesRef.current;

    if (newIndex < currentImages.length) {
      setCurrentImage(currentImages[newIndex]);

      if (newIndex < currentImages.length - 1) {
        imageTimeoutRef.current = setTimeout(
          () => changeImage(newIndex + 1),
          durationRef.current / currentImages.length,
        );
      }
    }
  };

  useEffect(() => {
    if (slide?.mediaData) {
      const imageUrls = getAllMediaUrlsFromField(
        slide.mediaData,
        content.image,
      );

      if (imageUrls?.length > 0) {
        const newImages = imageUrls.map((url) => ({
          url,
          nodeRef: createRef(),
        }));

        setImages(newImages);
      } else {
        setImages([]);
      }
    }
  }, [slide, content.image]);

  const clearImageTimeout = () => {
    if (imageTimeoutRef.current) {
      clearTimeout(imageTimeoutRef.current);
    }
  };

  const startTheShow = () => {
    clearImageTimeout();

    const currentImages = imagesRef.current;

    if (currentImages.length > 1) {
      // Kickoff the display of multiple images with the zero indexed
      changeImage(0);
    } else if (currentImages.length === 1) {
      setCurrentImage(currentImages[0]);
    }
  };

  useEffect(() => {
    if (images.length > 0) {
      startTheShow();
    } else {
      setCurrentImage(undefined);
    }

  }, [images]);

  useEffect(() => {
    if (run) {
      startTheShow();

      const slideExecution = new BaseSlideExecution(slide, slideDone);
      slideExecution.start(duration);

      return () => {
        slideExecution.stop();
        clearImageTimeout();
      };
    }

    return clearImageTimeout;
  }, [run, slide, slideDone, duration]);

  return (
    <>
      <div className={rootClasses.join(" ")}>
        <TransitionGroup component={null}>
          {currentImage && (
            <CSSTransition
              key={currentImage.url}
              timeout={1000}
              nodeRef={currentImage.nodeRef}
              classNames={`background-image${
                disableImageFade ? "-animation-disabled" : ""
              }`}
            >
              <div
                style={{
                  backgroundImage: currentImage.url
                    ? `url("${currentImage.url}")`
                    : "",
                }}
                ref={currentImage.nodeRef}
                className={`background-image${
                  mediaContain ? " media-contain" : ""
                }`}
              />
            </CSSTransition>
          )}
        </TransitionGroup>
        {(title || text) && (
          <div className={boxClasses} style={imageTextStyle}>
            {title && (
              <h1>
                {title}
                {/* Todo theme the color of the below */}
                {displaySeparator && <div className="separator" />}
              </h1>
            )}
            {sanitizedText && (
              <div className="text">{parse(sanitizedText)}</div>
            )}
          </div>
        )}

        {showLogo && logoUrl && (
          <img className={logoClasses.join(" ")} src={logoUrl} alt="" />
        )}
      </div>

      {slide?.theme?.cssStyles && (
        <ThemeStyles id={executionId} css={slide.theme.cssStyles} />
      )}
    </>
  );
}

export default { id, config, renderSlide };
