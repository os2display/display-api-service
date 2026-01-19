import { useEffect } from "react";
import templateConfig from "./custom-template-example.json";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";
import i18next from "i18next";
import adminTranslations from "./translations.json";
import { useTranslation } from "react-i18next";
import getInputFiles from "../utils/helper.js";

/**
 * Get the ULID of the template.
 * @return {string} The ULID of the template.
 */
function id() {
  return templateConfig.id;
}

/**
 * Get the config object of the template.
 * @return {{title: string, id: string, options: {}, adminForm: {}}}
 */
function config() {
  return templateConfig;
}

/**
 * Render the slide.
 * @param {object} slide The slide data.
 * @param {string} run A date string set when the slide should start running.
 * @param slideDone A function to invoke when the slide is done playing.
 * @return {JSX.Element} The component.
 */
function renderSlide(slide, run, slideDone) {
  return (
    <CustomTemplateExample
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

function renderAdminForm(formStateObject, onChange, handleMedia, mediaData) {
  return (
    <CustomTemplateAdminExample
      formStateObject={formStateObject}
      onChange={onChange}
      handleMedia={handleMedia}
      mediaData={mediaData}
    />
  );
}

/**
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {string} props.run A date string set when the slide should start running.
 * @param {Function} props.slideDone A function to invoke when the slide is done playing.
 * @param {string} props.executionId A unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function CustomTemplateExample({
  slide,
  content,
  run,
  slideDone,
  executionId,
}) {
  const { duration = 15000 } = content;
  const { title = "Default title" } = content;

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
      <div className="custom-template-example">
        <h1 className="title">{title}</h1>
      </div>

      {slide?.theme?.cssStyles && (
        <ThemeStyles id={executionId} css={slide.theme.cssStyles} />
      )}
    </>
  );
}

/**
 * @param {object} props Props.
 * @param {object} props.slideContent The slide content.
 * @param {Function} props.onSlideContentChange on slide content change.
 * @param {Function} props.handleMedia on slide media change.
 * @param {object} props.mediaData The media object.
 * @returns {JSX.Element} The component.
 */
function CustomTemplateAdminExample({
  slideContent,
  onSlideContentChange,
  handleMedia = () => {},
  mediaData = {},
}) {
  const { t } = useTranslation("custom-template-admin-example");

  useEffect(() => {
    const currentLang = i18next.language;
    if (
      !i18next.hasResourceBundle(currentLang, "custom-template-admin-example")
    ) {
      i18next.addResourceBundle(
        currentLang,
        "custom-template-admin-example",
        adminTranslations["custom-template-admin-example"],
        true,
        true,
      );
    }
  }, []);

  return (
    <>
      <h2 className="h4 mb-3">{t("header")}</h2>
      <fieldset>
        <legend className="h5 mb-3">{t("content-sub-header")}</legend>
        <label className="form-label">
          {t("slide-title-label")}
          <textarea
            onChange={onSlideContentChange}
            id="title"
            className="col-md-6 form-control"
            rows="3"
            defaultValue={slideContent["title"]}
          />
          <small className="form-text d-flex">
            {t("slide-title-help-text")}
          </small>
        </label>
        <label className="form-label mb-0 col-9">
          {t("images-label")}
          <FileSelector
            files={getInputFiles(slideContent["image"], mediaData)}
            multiple={true}
            onFilesChange={handleMedia}
            name="image"
            acceptedMimetypes="image/*"
          />
        </label>
        <small>{t("images-help-text")}</small>
      </fieldset>
    </>
  );
}

export default { id, config, renderSlide, renderAdminForm };
