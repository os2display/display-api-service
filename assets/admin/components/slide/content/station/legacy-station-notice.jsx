import { Alert } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import { Link } from "react-router-dom";

/**
 * Deprecation notice for travel slides that store stations in slide content.
 *
 * Stations are now chosen through a Rejseplanen feed source (#361). Slides
 * with stations in content keep rendering them until they are upgraded.
 *
 * @param {object} props The props.
 * @param {Array} props.stations The legacy stations from slide content.
 * @returns {object | null} The notice, or null when there are no legacy stations.
 */
function LegacyStationNotice({ stations = [] }) {
  const { t } = useTranslation("common", {
    keyPrefix: "legacy-station-notice",
  });

  if (!Array.isArray(stations) || stations.length === 0) {
    return null;
  }

  return (
    <Alert variant="warning" className="mb-3" id="legacy-station-notice">
      <Alert.Heading as="h4" className="h5">
        {t("heading")}
      </Alert.Heading>
      <p>{t("description")}</p>
      <ul>
        {stations.map(({ id, name }) => (
          <li key={id}>{name ?? id}</li>
        ))}
      </ul>
      <p className="mb-1">{t("how-to-upgrade")}</p>
      <ol>
        <li>
          {t("step-create-feed-source")}{" "}
          <Link to="/feed-sources/create">{t("create-feed-source-link")}</Link>
        </li>
        <li>{t("step-select-stations")}</li>
        <li>{t("step-save")}</li>
      </ol>
      <p className="mb-0">{t("until-upgraded")}</p>
    </Alert>
  );
}

export default LegacyStationNotice;
