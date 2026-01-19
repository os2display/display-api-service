import React, { useEffect, Fragment, useState } from "react";
import dayjs from "dayjs";
import localizedFormat from "dayjs/plugin/localizedFormat";
import { FormattedMessage, IntlProvider } from "react-intl";
import BaseSlideExecution from "../slide-utils/base-slide-execution";
import da from "./brnd/lang/da.json";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util";
import BrndSportcenterToday from "./brnd/brnd-sportcenter-today";
import GlobalStyles from "../slide-utils/GlobalStyles";
import "./brnd/brnd.scss";
import templateConfig from "./brnd.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Brnd
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Brnd component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {string} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Brnd({ slide, content, run, slideDone, executionId }) {
  const [translations, setTranslations] = useState();

  const {
    layout = "sportcenter-today",
    duration = 15000,
    fontSize,
    resourceUnavailableText,
  } = content;
  const { feedData = [] } = slide;

  const classes = ["template-brnd", fontSize];
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
        {layout === "sportcenter-today" && (
          <BrndSportcenterToday
            bookings={feedData.bookings}
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
