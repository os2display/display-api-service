import { React } from "react";
import { useTranslation } from "react-i18next";
import FormInput from "../../util/forms/form-input";

const NotifiedFeedType = ({ handleInput, formStateObject, mode }) => {
  const { t } = useTranslation("common", {
    keyPrefix: "feed-source-manager.dynamic-fields.notified-feed-type",
  });

  return (
    <>
      <FormInput
        name="token"
        type="text"
        label={t("token")}
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject.token}
      />
    </>
  );
};

export default NotifiedFeedType;
