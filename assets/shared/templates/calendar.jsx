import React, { useEffect, Fragment, useState } from "react";
import dayjs from "dayjs";
import localizedFormat from "dayjs/plugin/localizedFormat";
import { FormattedMessage, IntlProvider } from "react-intl";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import da from "./calendar/lang/da.json";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import CalendarSingle from "./calendar/calendar-single.jsx";
import CalendarSingleBooking from "./calendar/calendar-single-booking.jsx";
import CalendarMultipleDays from "./calendar/calendar-multiple-days.jsx";
import CalendarMultiple from "./calendar/calendar-multiple.jsx";
import GlobalStyles from "../slide-utils/GlobalStyles.js";
import "./calendar/calendar.scss";
import templateConfig from "./calendar.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Calendar
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Calendar component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {string} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Calendar({ slide, content, run, slideDone, executionId }) {
  const [translations, setTranslations] = useState();

  const {
    layout = "multiple",
    duration = 15000,
    fontSize,
    resourceUnavailableText,
  } = content;
  const { feedData = [] } = slide;

  const classes = ["template-calendar", fontSize];
  const rootStyle = {};

  const imageUrl = getFirstMediaUrlFromField(slide.mediaData, content.image);

  if (imageUrl) {
    rootStyle["--bg-image"] = `url("${imageUrl}")`;
  }

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

  /** Imports language strings, sets localized formats. */
  useEffect(() => {
    dayjs.extend(localizedFormat);

    setTranslations(da);
  }, []);

  const getTitle = (eventTitle) => {
    if (!eventTitle || eventTitle === "") {
      if (resourceUnavailableText) {
        return resourceUnavailableText;
      }

      return <FormattedMessage id="unavailable" defaultMessage="Unavailable" />;
    }

    return eventTitle;
  };

  return (
    <>
      <IntlProvider messages={translations} locale="da" defaultLocale="da">
        {layout === "single" && (
          <CalendarSingle
            calendarEvents={feedData}
            content={content}
            templateClasses={classes}
            templateRootStyle={rootStyle}
            getTitle={getTitle}
          />
        )}
        {layout === "singleBooking" && (
          <CalendarSingleBooking
            slide={slide}
            calendarEvents={feedData}
            content={content}
            templateClasses={classes}
            templateRootStyle={rootStyle}
            getTitle={getTitle}
            run={run}
          />
        )}
        {layout === "multiple" && (
          <CalendarMultiple
            calendarEvents={feedData}
            content={content}
            templateClasses={classes}
            templateRootStyle={rootStyle}
            getTitle={getTitle}
          />
        )}
        {layout === "multipleDays" && (
          <CalendarMultipleDays
            calendarEvents={feedData}
            content={content}
            templateClasses={classes}
            templateRootStyle={rootStyle}
            getTitle={getTitle}
          />
        )}
      </IntlProvider>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
      <GlobalStyles />
    </>
  );
}

export default { id, config, renderSlide };
