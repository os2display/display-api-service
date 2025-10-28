import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Alert } from "react-bootstrap";
import MultiselectFromEndpoint from "../../slide/content/multiselect-from-endpoint";

const CalendarApiFeedType = ({
  feedSourceId,
  handleInput,
  formStateObject,
}) => {
  const { t } = useTranslation("common", {
    keyPrefix: "feed-source-manager.dynamic-fields.calendar-api-feed-type",
  });

  const [optionsEndpoint, setOptionsEndpoint] = useState(null);

  useEffect(() => {
    if (feedSourceId && feedSourceId !== "") {
      const endpoint = "/" + feedSourceId + "/config/locations";
      setOptionsEndpoint(endpoint);
    }
  }, [feedSourceId]);

  return (
    <>
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
          value={formStateObject.locations ?? []}
          optionsEndpoint={optionsEndpoint}
        />
      )}
    </>
  );
};

export default CalendarApiFeedType;
