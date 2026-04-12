import { useEffect, useRef, useState } from "react";
import dayjs from "dayjs";
import localeDa from "dayjs/locale/da";
import localizedFormat from "dayjs/plugin/localizedFormat";
import { IntlProvider, FormattedMessage } from "react-intl";
import da from "./poster/lang/da.json";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";
import useMultipleEntrySlideExecution from "../slide-utils/useMultipleEntrySlideExecution.js";
import "../slide-utils/global-styles.css";
import "./poster/poster.scss";
import templateConfig from "./poster.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Poster
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Poster component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Poster({ slide, content, run, slideDone, executionId }) {
  const [translations, setTranslations] = useState({});
  const [show, setShow] = useState(true);
  const fallbackRef = useRef(null);
  const logo = slide?.theme?.logo;
  const { showLogo, mediaContain } = content;

  const logoUrl = showLogo && logo?.assets?.uri ? logo.assets.uri : "";

  const { feed, feedData } = slide;

  // Animation.
  const animationDuration = 500;
  const { duration = 15000 } = content; // default 15s.

  const feedEntries = feedData ?? [];

  const { currentEntry: currentEvent } = useMultipleEntrySlideExecution({
    entries: feedEntries,
    run,
    slide,
    slideDone,
    entryDuration: duration,
  });

  // Props from currentEvent.
  const {
    endDate,
    startDate,
    name,
    image,
    excerpt,
    ticketPriceRange,
    url,
    place,
  } = currentEvent ?? {};

  const { configuration = {} } = feed ?? {};

  const {
    overrideTitle = "",
    overrideSubTitle = "",
    overrideTicketPrice = "",
    overrideReadMoreUrl = "",
    hideTime = false,
    readMoreText = "",
  } = configuration;

  // Dates.
  const singleDayEvent =
    endDate &&
    new Date(endDate).toDateString() === new Date(startDate).toDateString();

  /**
   * Capitalize the datestring, as it starts with the weekday.
   *
   * @param {string} s The string to capitalize.
   * @returns {string} The capitalized string.
   */
  const capitalize = (s) => {
    return s.charAt(0).toUpperCase() + s.slice(1);
  };

  const formatDate = (date) => {
    if (!date) return "";
    return capitalize(dayjs(date).locale(localeDa).format("ll"));
  };

  const formatTime = (date) => {
    if (!date) return "";
    return capitalize(dayjs(date).locale(localeDa).format("LT"));
  };

  const formatDateNoYear = (date) => {
    if (!date) return "";
    return capitalize(dayjs(date).locale(localeDa).format("DD MMM"));
  };

  const getUrlDomain = (urlString) => {
    return (
      urlString
        // Remove scheme
        .replace(/^[^:]*:\/\//, "")
        // Remove paths
        .replace(/\/.*$/, "")
    );
  };

  // Trigger fade-out animation before entry changes.
  useEffect(() => {
    if (!currentEvent) return;

    setShow(true);
    const animationTimer = setTimeout(
      () => {
        setShow(false);
      },
      duration - animationDuration + 50,
    );

    return () => clearTimeout(animationTimer);
  }, [currentEvent]);

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

  // Imports language strings and sets localized formats.
  useEffect(() => {
    dayjs.extend(localizedFormat);
    setTranslations(da);
  }, []);

  return (
    <>
      {/* TODO: Adjust styling to variables from Theme */}
      {currentEvent !== null && (
        <IntlProvider messages={translations} locale="da" defaultLocale="da">
          <div className={`template-poster ${showLogo && "with-logo"}`}>
            <div
              className={`image-area ${mediaContain ? "media-contain" : ""}`}
              style={
                image
                  ? {
                      backgroundImage: `url("${image}")`,
                      ...(show
                        ? { animation: `fade-in ${animationDuration}ms` }
                        : { animation: `fade-out ${animationDuration}ms` }),
                    }
                  : {}
              }
            />
            <div className="header-area">
              <div className="center">
                <h1>
                  {!overrideTitle && name}
                  {overrideTitle}
                </h1>
                <p className="lead">
                  {!overrideSubTitle && excerpt}
                  {overrideSubTitle}
                </p>
              </div>
            </div>
            <div className="info-area">
              <div className="center">
                {!hideTime && startDate && (
                  <span>
                    {singleDayEvent && (
                      <span>
                        <div className="date">{formatDate(startDate)}</div>
                        <div className="date">
                          {formatTime(startDate)} - {formatTime(endDate)}
                        </div>
                      </span>
                    )}
                    {/* todo if startdate is not equal to enddate */}
                    {!singleDayEvent && (
                      <span>
                        <div className="date">
                          {startDate && formatDateNoYear(startDate)} -{" "}
                          {endDate && formatDate(endDate)}
                        </div>
                        <div className="date">
                          {formatTime(startDate)} - {formatTime(endDate)}
                        </div>
                      </span>
                    )}
                  </span>
                )}
                {place && <p className="place">{place.name}</p>}
                <p className="ticket">
                  {!ticketPriceRange && (
                    <FormattedMessage id="free" defaultMessage="free" />
                  )}
                  {ticketPriceRange && (
                    <>
                      {!overrideTicketPrice && ticketPriceRange}
                      {overrideTicketPrice}
                    </>
                  )}
                </p>
                <>
                  {readMoreText && <p className="moreinfo">{readMoreText}</p>}
                  {!overrideReadMoreUrl && url && (
                    <span className="look-like-link">{getUrlDomain(url)}</span>
                  )}
                  {overrideReadMoreUrl && (
                    <span className="look-like-link">
                      {overrideReadMoreUrl}
                    </span>
                  )}
                </>
              </div>
            </div>
            {showLogo && (
              <div className="logo-area">
                <img src={logoUrl} alt="" />
              </div>
            )}
          </div>
        </IntlProvider>
      )}

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
    </>
  );
}

export default { id, config, renderSlide };
