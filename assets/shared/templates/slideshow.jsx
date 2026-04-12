import { useState, useRef, useEffect } from "react";
import {
  getAllMediaUrlsFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import useMultipleEntrySlideExecution from "../slide-utils/useMultipleEntrySlideExecution.js";
import "../slide-utils/global-styles.css";
import "./slideshow/slideshow.scss";
import templateConfig from "./slideshow.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Slideshow
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Slideshow component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Slideshow({ slide, content, run, slideDone, executionId }) {
  const {
    images,
    imageDuration = 5,
    transition,
    animation,
    mediaContain,
  } = content;

  // Map images to mediaData.
  const imageUrls = getAllMediaUrlsFromField(slide.mediaData, images);

  const imageDurationInMilliseconds = imageDuration * 1000;

  const [fade, setFade] = useState(false);
  const [animationIndex, setAnimationIndex] = useState(0);
  const [animationKeyframesCurrent, setAnimationKeyframesCurrent] = useState("");
  const [animationKeyframesNext, setAnimationKeyframesNext] = useState("");
  const fallbackRef = useRef(null);

  const fadeEnabled = transition === "fade";
  const fadeDuration = 1000;
  const fadeSafeMargin = 50;

  const getAnimationName = (i) => `animationForImage-${executionId}-${i % 2}`;
  const animationDuration =
    imageDurationInMilliseconds + (fadeEnabled ? fadeDuration * 2 : 0);

  const { showLogo, logoSize, logoPosition, logoMargin } = content;
  const logo = slide?.theme?.logo;
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

  const { entryIndex: index } = useMultipleEntrySlideExecution({
    entries: imageUrls,
    run,
    slide,
    slideDone,
    entryDuration: imageDurationInMilliseconds,
  });

  /**
   * A random function to simplify the code where random is used
   *
   * @param {number} multiplier The multiplier.
   * @returns {number} Random number.
   */
  function random(multiplier) {
    return Math.floor(Math.random() * multiplier);
  }

  /**
   * Creates the animation
   *
   * @param {boolean} grow Grow boolean.
   * @param {string} transform The transform.
   * @returns {string} The animation.
   */
  function createAnimation(name, grow, transform = "50% 50%") {
    const transformOrigin = transform;
    const startSize = grow ? 1 : 1.2;
    const finishSize = grow ? 1.2 : 1;

    return `@keyframes ${name} {
      0% {
        transform: scale(${startSize});
        transform-origin: ${transformOrigin};
      }
      5% {
        transform: scale(${startSize});
        transform-origin: ${transformOrigin};
      }
      95% {
        transform: scale(${finishSize});
        transform-origin: ${transformOrigin};
      }
      100% {
        transform: scale(${finishSize});
        transform-origin: ${transformOrigin};
      }
    }`;
  }

  /**
   * Determines which animation should be used
   *
   * @param {string} animationType The animation type.
   * @returns {string | null} The current animation.
   */
  function getCurrentAnimation(name, animationType) {
    const animationTypes = [
      "zoom-in-middle",
      "zoom-out-middle",
      "zoom-out-random",
      "zoom-in-random",
    ];

    const randomPercent = `${random(100) + 1}% ${random(100) + 1}%`;
    switch (animationType) {
      case "zoom-in-middle":
        return createAnimation(name, true);
      case "zoom-out-middle":
        return createAnimation(name, false);
      case "zoom-in-random":
        return createAnimation(name, true, randomPercent);
      case "zoom-out-random":
        return createAnimation(name, false, randomPercent);
      case "random":
        return getCurrentAnimation(
          name,
          animationTypes[random(animationTypes.length)],
        );
      default:
        return null;
    }
  }

  // Get image style for the given image url.
  const getImageStyle = (
    imageUrl,
    imageIndex,
    enableAnimation,
    localAnimationDuration,
  ) => {
    const imageStyle = {
      backgroundImage: `url(${imageUrl})`,
    };

    if (enableAnimation) {
      imageStyle.animation = `${getAnimationName(imageIndex)} ${localAnimationDuration}ms`;
    }

    return imageStyle;
  };

  // If there are no images in slide, wait for 2s before continuing to avoid crashes.
  useEffect(() => {
    if (run && imageUrls.length === 0) {
      fallbackRef.current = setTimeout(() => slideDone(slide), 2000);
    }

    return () => {
      if (fallbackRef.current) {
        clearTimeout(fallbackRef.current);
      }
    };
  }, [run]);

  // Regenerate animation keyframes and trigger fade for each image.
  // Pre-start the scale animation on the next image during the fade so
  // the zoom is already in progress when the image becomes visible.
  useEffect(() => {
    setAnimationIndex(index);
    setFade(false);

    if (animation) {
      const keyframes =
        getCurrentAnimation(getAnimationName(index), animation) ?? "";
      setAnimationKeyframesCurrent(keyframes);
    }

    if (!fadeEnabled) return;

    const fadeTimer = setTimeout(() => {
      setFade(true);

      const nextIndex = index + 1;
      if (nextIndex < imageUrls.length) {
        setAnimationIndex(nextIndex);

        if (animation) {
          const nextKeyframes =
            getCurrentAnimation(getAnimationName(nextIndex), animation) ?? "";
          setAnimationKeyframesNext(nextKeyframes);
        }
      }
    }, imageDurationInMilliseconds - fadeDuration + fadeSafeMargin);

    return () => clearTimeout(fadeTimer);
  }, [index]);

  return (
    <>
      <div className="template-slideshow">
        {imageUrls &&
          imageUrls.map((imageUrl, imageUrlIndex) => {
            const className = "fade-container";
            const current = imageUrlIndex === index;
            const containerStyle = {
              opacity: 0,
              zIndex: imageUrls.length - imageUrlIndex,
            };

            if (current) {
              if (fadeEnabled) {
                if (index === 0) {
                  containerStyle.animation = `fadeIn ${fadeDuration}ms`;
                }
                if (fade) {
                  // Fade out current slide.
                  containerStyle.animation = `fadeOut ${fadeDuration}ms`;
                } else {
                  containerStyle.opacity = 1;
                }
              } else {
                containerStyle.opacity = 1;
              }
            } else if (imageUrlIndex === index + 1) {
              if (fade) {
                // Fade in next slide.
                containerStyle.animation = `fadeIn ${fadeDuration}ms`;
              }
            }

            return (
              <div
                className={className}
                key={imageUrl}
                data-index={imageUrlIndex}
                style={containerStyle}
                data-active={current}
              >
                <div
                  style={getImageStyle(
                    imageUrl,
                    imageUrlIndex,
                    animationIndex === imageUrlIndex || index === imageUrlIndex,
                    animationDuration,
                  )}
                  className={`image${mediaContain ? " media-contain" : ""}`}
                />
              </div>
            );
          })}

        {showLogo && logoUrl && (
          <img className={logoClasses.join(" ")} src={logoUrl} alt="" />
        )}
      </div>

      {(animationKeyframesCurrent || animationKeyframesNext) && (
        <style>
          {animationKeyframesCurrent}
          {animationKeyframesNext}
        </style>
      )}
      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

export default { id, config, renderSlide };
