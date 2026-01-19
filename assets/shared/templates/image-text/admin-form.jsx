import { useEffect } from "react";
import RichText from "../../forms/rich-text/rich-text.jsx";
import Select from "../../forms/select.jsx";
import FileSelector from "../../../admin/components/slide/content/file-selector.jsx";
import getInputFiles from "../../utils/helper.js";
import FormCheckbox from "../../forms/form-checkbox.jsx";
import FormInput from "../../forms/form-input.jsx";
import i18next from "i18next";
import adminTranslations from "./translations.json";
import { useTranslation } from "react-i18next";

function ImageTextAdminForm({
  slideContent,
  onSlideContentChange,
  handleMedia = () => {},
  mediaData = {},
}) {
  const { t } = useTranslation("image-text-admin");

  useEffect(() => {
    const currentLang = i18next.language;
    if (!i18next.hasResourceBundle(currentLang, "image-text-admin")) {
      i18next.addResourceBundle(
        currentLang,
        "image-text-admin",
        adminTranslations["image-text-admin"],
        true,
        true,
      );
    }
  }, []);

  return (
    <>
      <h2 className="h4 mb-3">{t("image-text-header")}</h2>
      <fieldset>
        <legend className="h5 mb-3">{t("content-sub-header")}</legend>

        <label htmlFor="image-text-1" className="form-label">
          {t("slide-title-label")}
        </label>
        <textarea
          onChange={onSlideContentChange}
          id="title"
          className="col-md-9 form-control"
          rows="3"
          defaultValue={slideContent?.title}
        />
        <small className="form-text d-flex">{t("slide-title-help-text")}</small>

        <RichText
          name="text"
          label={t("rich-text-label")}
          helpText={t("rich-text-help-text")}
          value={slideContent?.text}
          onChange={onSlideContentChange}
          formGroupClasses="col-md mt-3"
        />

        <Select
          value={slideContent?.fontSize}
          name={"fontSize"}
          options={[
            { key: "fontsize1", title: "xs", value: "font-size-xs" },
            { key: "fontsize2", title: "s", value: "font-size-s" },
            { key: "fontsize3", title: "m", value: "font-size-m" },
            { key: "fontsize4", title: "l", value: "font-size-lg" },
            { key: "fontsize5", title: "xl", value: "font-size-xl" },
          ]}
          onChange={onSlideContentChange}
          label={t("font-size-label")}
          formGroupClasses="col-md-9 my-4"
        />

        <label className="form-label mb-0 col-9">
          {t("images-label")}
          <FileSelector
            files={getInputFiles(slideContent?.image, mediaData)}
            multiple={true}
            onFilesChange={handleMedia}
            name="image"
            acceptedMimetypes="image/*"
          />
        </label>
        <div>
          <small>{t("images-help-text")}</small>
        </div>

        <FormCheckbox
          label={t("disable-cropping-label")}
          onChange={onSlideContentChange}
          name="mediaContain"
          formGroupClasses="mt-3"
          helpText={t("disable-cropping-help-text")}
          value={slideContent?.mediaContain}
        />
      </fieldset>

      <fieldset>
        <legend className="h5 mt-3 mb-3">{t("settings-sub-header")}</legend>

        <FormInput
          name="duration"
          min="1"
          type="number"
          label={t("duration-label")}
          helpText={t("duration-help-text")}
          formGroupClasses="col-md-9 mb-3"
          value={
            slideContent?.duration
              ? Math.floor(slideContent.duration / 1000)
              : 15
          }
          onChange={(value) => {
            const newValue = value.target.value;
            onSlideContentChange({
              target: { id: "duration", value: Math.max(newValue, 1) * 1000 },
            });
          }}
        />

        <Select
          value={slideContent?.boxAlign}
          name="boxAlign"
          options={[
            {
              key: "placement1",
              title: t("box-align-options.top"),
              value: "top",
            },
            {
              key: "placement2",
              title: t("box-align-options.right"),
              value: "right",
            },
            {
              key: "placement3",
              title: t("box-align-options.bottom"),
              value: "bottom",
            },
            {
              key: "placement4",
              title: t("box-align-options.left"),
              value: "left",
            },
          ]}
          onChange={onSlideContentChange}
          label={t("box-align-label")}
          formGroupClasses="col-md-9 mb-3"
        />

        <FormCheckbox
          label={t("box-margin-label")}
          onChange={onSlideContentChange}
          name="boxMargin"
          formGroupClasses="col-md-9 mb-3"
          value={slideContent?.boxMargin}
        />

        <FormCheckbox
          label={t("separator-label")}
          onChange={onSlideContentChange}
          name="separator"
          formGroupClasses="col-md-9 mb-3"
          value={slideContent?.separator}
        />

        {slideContent && (
          <FormCheckbox
            label={t("reversed-layout-label")}
            onChange={onSlideContentChange}
            name="reversed"
            disabled={!slideContent.separator}
            helpText={t("reversed-layout-help-text")}
            formGroupClasses="col-md-9 mb-3"
            value={slideContent?.reversed}
          />
        )}

        <FormCheckbox
          label={t("half-size-label")}
          onChange={onSlideContentChange}
          name="halfSize"
          formGroupClasses="col-md-9 mb-3"
          value={slideContent?.halfSize}
        />

        <FormCheckbox
          label={t("shadow-label")}
          onChange={onSlideContentChange}
          name="shadow"
          formGroupClasses="col-md-9 mb-3"
          value={slideContent?.shadow}
        />
        <fieldset>
          <legend className="form-label">{t("logo-settings-legend")}</legend>

          <FormCheckbox
            label={t("show-logo-label")}
            onChange={onSlideContentChange}
            name="showLogo"
            formGroupClasses="col-md-9 mb-3"
            value={slideContent?.showLogo}
          />
          <small className="form-text d-flex mb-2">
            {t("logo-settings-help-text")}
          </small>

          {slideContent && (
            <>
              <Select
                disabled={!slideContent.showLogo}
                value={slideContent?.logoSize}
                name="logoSize"
                options={[
                  {
                    key: "logosize1",
                    title: t("logo-size-options.logo-size-s"),
                    value: "logo-size-s",
                  },
                  {
                    key: "logosize2",
                    title: t("logo-size-options.logo-size-m"),
                    value: "logo-size-m",
                  },
                  {
                    key: "logosize3",
                    title: t("logo-size-options.logo-size-l"),
                    value: "logo-size-l",
                  },
                ]}
                onChange={onSlideContentChange}
                label={t("logo-size-label")}
                formGroupClasses="col-md-9 mb-3"
              />

              <Select
                disabled={!slideContent.showLogo}
                value={slideContent?.logoPosition}
                name="logoPosition"
                options={[
                  {
                    key: "logoposition1",
                    title: t("logo-position-options.logo-position-top-left"),
                    value: "logo-position-top-left",
                  },
                  {
                    key: "logoposition2",
                    title: t("logo-position-options.logo-position-top-right"),
                    value: "logo-position-top-right",
                  },
                  {
                    key: "logoposition3",
                    title: t("logo-position-options.logo-position-bottom-left"),
                    value: "logo-position-bottom-left",
                  },
                  {
                    key: "logoposition4",
                    title: t(
                      "logo-position-options.logo-position-bottom-right",
                    ),
                    value: "logo-position-bottom-right",
                  },
                ]}
                onChange={onSlideContentChange}
                label={t("logo-position-label")}
                formGroupClasses="col-md-9 mb-3"
              />

              <FormCheckbox
                disabled={!slideContent.showLogo}
                label={t("logo-margin-label")}
                onChange={onSlideContentChange}
                name="logoMargin"
                formGroupClasses="col-md-9 mb-3"
                value={slideContent?.logoMargin}
              />
            </>
          )}
        </fieldset>
        {Object.keys(mediaData).length > 1 && (
          <FormCheckbox
            label={t("disable-fade-label")}
            onChange={onSlideContentChange}
            name="disableImageFade"
            formGroupClasses="col-md-9 mb-3"
            value={slideContent?.disableImageFade}
          />
        )}
      </fieldset>
    </>
  );
}

export default ImageTextAdminForm;
