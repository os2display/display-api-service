import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Alert } from "react-bootstrap";
import MultiselectFromEndpoint from "../../slide/content/multiselect-from-endpoint";
import FormInput from "../../util/forms/form-input";

const ColiboFeedType = ({
  feedSourceId,
  handleInput,
  formStateObject,
  mode,
}) => {
  const { t } = useTranslation("common", {
    keyPrefix: "colibo-feed-type",
  });

  const [optionsEndpoint, setOptionsEndpoint] = useState(null);

  useEffect(() => {
    if (feedSourceId && feedSourceId !== "") {
      const endpoint = "/" + feedSourceId + "/config/recipients";
      setOptionsEndpoint(endpoint);
    }
  }, [feedSourceId]);

  return (
    <>
      {!feedSourceId && (
        <Alert className="mt-4" variant="warning">
          {t("save-before-recipients-can-be-set")}
        </Alert>
      )}

      <FormInput
        name="api_base_uri"
        type="text"
        label={t("api-base-uri")}
        className="mb-2"
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.api_base_uri}
      />

      <FormInput
        name="client_id"
        type="text"
        className="mb-2"
        label={t("client-id")}
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.client_id}
      />

      <FormInput
        name="client_secret"
        type="text"
        label={t("client-secret")}
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.client_secret}
      />

      <Alert className="mt-4" variant="info">
        {t("values-info")}
      </Alert>

      {optionsEndpoint && (
        <MultiselectFromEndpoint
          onChange={handleInput}
          name="allowed_recipients"
          disableSearch={false}
          label={t("allowed-recipients")}
          value={formStateObject.allowed_recipients ?? []}
          optionsEndpoint={optionsEndpoint}
          helpText={t("allowed-recipients-help")}
        />
      )}
    </>
  );
};

export default ColiboFeedType;
