import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Alert } from "react-bootstrap";
import MultiselectFromEndpoint from "../../slide/content/multiselect-from-endpoint";
import FormInput from "../../util/forms/form-input";

const MsGraphFeedType = ({
  feedSourceId,
  handleInput,
  formStateObject,
  mode,
}) => {
  const { t } = useTranslation("common", {
    keyPrefix: "ms-graph-feed-type",
  });

  const [optionsEndpoint, setOptionsEndpoint] = useState(null);

  useEffect(() => {
    if (feedSourceId && feedSourceId !== "") {
      const endpoint = `${feedSourceId}/config/locations`;
      setOptionsEndpoint(endpoint);
    }
  }, [feedSourceId]);

  return (
    <>
      <FormInput
        name="tenant_id"
        type="text"
        label={t("tenant-id")}
        formGroupClasses="mb-2"
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.tenant_id}
      />

      <FormInput
        name="client_id"
        type="text"
        formGroupClasses="mb-2"
        label={t("client-id")}
        helpText={t("client-id-help")}
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.client_id}
      />

      <FormInput
        name="client_secret"
        type="password"
        label={t("client-secret")}
        formGroupClasses="mb-2"
        onChange={handleInput}
        placeholder={
          mode === "PUT" ? t("redacted-value-input-placeholder") : ""
        }
        value={formStateObject?.client_secret}
      />

      <Alert className="mt-4" variant="info">
        {t("values-info")}
      </Alert>

      {!feedSourceId && (
        <Alert className="mt-4" variant="warning">
          {t("save-before-locations-can-be-set")}
        </Alert>
      )}

      {optionsEndpoint && (
        <MultiselectFromEndpoint
          onChange={handleInput}
          name="locations"
          disableSearch={false}
          label={t("locations")}
          helpText={t("locations-help")}
          value={formStateObject.locations ?? []}
          optionsEndpoint={optionsEndpoint}
        />
      )}
    </>
  );
};

export default MsGraphFeedType;
