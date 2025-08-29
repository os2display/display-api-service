import { useEffect } from "react";
import RichText from "../../../admin/components/util/forms/rich-text/rich-text.jsx";
import Select from "../../../admin/components/util/forms/select";
import FileSelector from "../../../admin/components/slide/content/file-selector.jsx";
import getInputFiles from "../../admin-util/helper.js";
import FormCheckbox from "../../../admin/components/util/forms/form-checkbox";
import FormInput from "../../../admin/components/util/forms/form-input";
import i18next from "i18next";
import adminTranslations from "./translations.json";
import { useTranslation } from "react-i18next";

function ImageTextAdmin({
  formStateObject,
  onChange,
  handleMedia,
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
          onChange={onChange}
          id="title"
          className="col-md-6 form-control"
          rows="3"
          defaultValue={formStateObject["title"]}
        />
        <small className="form-text d-flex">{t("slide-title-help-text")}</small>

        <RichText
          name="text"
          label={t("rich-text-label")}
          helpText={t("rich-text-help-text")}
          value={formStateObject["text"]}
          onChange={onChange}
          formGroupClasses="col-md mt-3"
        />

        <Select
          value={formStateObject["fontSize"]}
          name={"fontSize"}
          options={[
            { key: "fontsize1", title: "xs", value: "font-size-xs" },
            { key: "fontsize2", title: "s", value: "font-size-s" },
            { key: "fontsize3", title: "m", value: "font-size-m" },
            { key: "fontsize4", title: "l", value: "font-size-lg" },
            { key: "fontsize5", title: "xl", value: "font-size-xl" },
          ]}
          onChange={onChange}
          label={t("font-size-label")}
          formGroupClasses="col-md-6 my-4"
        />

        <label className="form-label mb-0 col-9">
          {t("images-label")}
          <FileSelector
            files={getInputFiles(formStateObject["image"], mediaData)}
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
          onChange={onChange}
          name="mediaContain"
          formGroupClasses="mt-3"
          helpText={t("disable-cropping-help-text")}
          value={formStateObject["mediaContain"]}
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
          formGroupClasses="col-md-6 mb-3"
          value={
            formStateObject["duration"]
              ? Math.floor(formStateObject["duration"] / 1000)
              : 15
          }
          onChange={(value) => {
            const newValue = value.target.value;
            onChange({
              target: { id: "duration", value: Math.max(newValue, 1) * 1000 },
            });
          }}
        />

        <Select
          value={formStateObject["boxAlign"]}
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
          onChange={onChange}
          label={t("box-align-label")}
          formGroupClasses="col-md-6 mb-3"
        />

        <FormCheckbox
          label={t("box-margin-label")}
          onChange={onChange}
          name="boxMargin"
          formGroupClasses="col-md-6 mb-3"
          value={formStateObject["boxMargin"]}
        />

        <FormCheckbox
          label={t("separator-label")}
          onChange={onChange}
          name="separator"
          formGroupClasses="col-md-6 mb-3"
          value={formStateObject["separator"]}
        />

        <FormCheckbox
          label={t("half-size-label")}
          onChange={onChange}
          name="halfSize"
          formGroupClasses="col-md-6 mb-3"
          value={formStateObject["halfSize"]}
        />

        {formStateObject["separator"] && (
          <FormCheckbox
            label={t("reversed-layout-label")}
            onChange={onChange}
            name="reversed"
            formGroupClasses="col-md-6 mb-3"
            value={formStateObject["reversed"]}
          />
        )}

        <FormCheckbox
          label={t("shadow-label")}
          onChange={onChange}
          name="shadow"
          formGroupClasses="col-md-6 mb-3"
          value={formStateObject["shadow"]}
        />

        <FormCheckbox
          label={t("show-logo-label")}
          onChange={onChange}
          name="showLogo"
          formGroupClasses="col-md-6 mb-3"
          value={formStateObject["showLogo"]}
        />

        {formStateObject["showLogo"] && (
          <>
            <Select
              value={formStateObject["logoSize"]}
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
              onChange={onChange}
              label={t("logo-size-label")}
              formGroupClasses="col-md-6 mb-3"
            />

            <Select
              value={formStateObject["logoPosition"]}
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
                  title: t("logo-position-options.logo-position-bottom-right"),
                  value: "logo-position-bottom-right",
                },
              ]}
              onChange={onChange}
              label={t("logo-position-label")}
              formGroupClasses="col-md-6 mb-3"
            />

            <FormCheckbox
              label={t("logo-margin-label")}
              onChange={onChange}
              name="logoMargin"
              formGroupClasses="col-md-6 mb-3"
              value={formStateObject["logoMargin"]}
            />
          </>
        )}

        {Object.keys(mediaData).length > 1 && (
          <FormCheckbox
            label={t("disable-fade-label")}
            onChange={onChange}
            name="disableImageFade"
            formGroupClasses="col-md-6 mb-3"
            value={formStateObject["disableImageFade"]}
          />
        )}
      </fieldset>
    </>
  );
}

export default ImageTextAdmin;
