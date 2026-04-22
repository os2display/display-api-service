import { useEffect, useRef } from "react";
import dayjs from "dayjs";
import localeDa from "dayjs/locale/da";
import localizedFormat from "dayjs/plugin/localizedFormat";
import styled from "styled-components";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import GlobalStyles from "../slide-utils/GlobalStyles.js";
import useMultipleEntrySlideExecution from "../slide-utils/useMultipleEntrySlideExecution.js";
import "./rss/rss.scss";
import templateConfig from "./rss.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <RSS
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Capitalize the datestring, as it starts with the weekday.
 *
 * @param {string} s The string to capitalize.
 * @returns {string} The capitalized string.
 */
const capitalize = (s) => {
  return s.charAt(0).toUpperCase() + s.slice(1);
};

/**
 * RSS component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {number} props.run Timestamp of when to start run.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function RSS({ slide, content, run, slideDone, executionId }) {
  const { fontSize = "m", image, mediaContain } = content;
  const { feedData = [], feed = {} } = slide ?? {};
  const { configuration = {} } = feed;
  const { entryDuration = 10, numberOfEntries = 5 } = configuration;
  const fallbackRef = useRef(null);

  const feedEntries = feedData?.entries?.slice(0, numberOfEntries) ?? [];

  const { currentEntry, entryIndex } = useMultipleEntrySlideExecution({
    entries: feedEntries,
    run,
    slide,
    slideDone,
    entryDuration: entryDuration * 1000,
  });

  /** Sets localized formats (dayjs) */
  useEffect(() => {
    dayjs.extend(localizedFormat);
  }, []);

  // If no content, wait 1 second and continue to next slide.
  useEffect(() => {
    if (run && feedEntries.length === 0) {
      fallbackRef.current = setTimeout(() => slideDone(slide), 1000);
    }

    return () => {
      if (fallbackRef.current) {
        clearTimeout(fallbackRef.current);
      }
    };
  }, [run]);

  const imageUrl = getFirstMediaUrlFromField(slide?.mediaData, image);

  const rootStyle = {};

  // Set background image.
  if (imageUrl) {
    rootStyle.backgroundImage = `url("${imageUrl}")`;
  }

  if (!slide?.feed) {
    return null;
  }

  return (
    <>
      <Wrapper
        className={`template-rss ${fontSize} ${
          mediaContain ? "media-contain" : ""
        }`}
        style={rootStyle}
      >
        <FeedInfo className="feed-info">
          {currentEntry?.lastModified && (
            <FeedDate className="feed-info--date">
              {capitalize(
                dayjs(currentEntry.lastModified)
                  .locale(localeDa)
                  .format("LLLL"),
              )}
            </FeedDate>
          )}
          <FeedTitle className="feed-info--title">
            {slide?.feedData?.title}
          </FeedTitle>
          {slide?.feed?.configuration?.showFeedProgress && (
            <FeedProgress className="feed-info--progress">
              {feedEntries.length > 0 && (
                <span className="feed-info--progress-numbers">
                  {entryIndex + 1} / {feedEntries.length}
                </span>
              )}
            </FeedProgress>
          )}
        </FeedInfo>
        <Content className="content">
          {currentEntry && (
            <>
              <Title className="title">{currentEntry.title}</Title>
              <Description className="description">
                {currentEntry.content}
              </Description>
            </>
          )}
        </Content>
      </Wrapper>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
      <GlobalStyles />
    </>
  );
}

const Wrapper = styled.div`
  --template-font-size: calc(var(--font-size-base) * 2.5);

  /* Wrapper styling */
  font-family: var(--font-family-base);
  font-size: var(--template-font-size);
  height: 100%;
  background-repeat: no-repeat;
  background-size: cover;
  background-color: var(--background-color);
  color: var(--text-color);
  overflow: hidden;
  padding: var(--spacer);
  display: flex;
  position: relative;
  flex-direction: column;
  gap: calc(var(--spacer) * 3);

  /* Position background from inline style */
  background-position: center;
`;

const FeedInfo = styled.div`
  display: flex;
  gap: var(--spacer);
`;

const FeedTitle = styled.div`
  && {
    // Override h1 font-size form styles applied with former class
    font-size: calc(var(--template-font-size) * 0.75);
  }
`;

const FeedDate = styled.div`
  && {
    // Override h1 font-size form styles applied with former class
    font-size: calc(var(--template-font-size) * 0.75);
  }
`;

const FeedProgress = styled.div`
  && {
    // Override h1 font-size form styles applied with former class
    font-size: calc(var(--template-font-size) * 0.75);
  }
`;

const Content = styled.div`
  display: flex;
  flex-direction: column;
  gap: var(--spacer);
`;

const Title = styled.h1`
  && {
    // Override h1 font-size form styles applied with former class
    font-size: calc(var(--template-font-size) * 2);
  }
  margin: 0;
`;

const Description = styled.p`
  margin: 0;
  a,
  a:link,
  a:visited,
  a:hover,
  a:focus,
  a:active {
    text-decoration: none;
    color: var(--text-color);
  }
`;

export default { id, config, renderSlide };
