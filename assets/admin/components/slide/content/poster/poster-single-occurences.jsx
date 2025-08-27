import { Button } from "react-bootstrap";
import { React } from "react";
import { useTranslation } from "react-i18next";
import { formatDate } from "./poster-helper";

/**
 * @param {object} props The props.
 * @param {Array} props.occurrences The occurrences.
 * @param {Function} props.handleSelectOccurrence The select callback.
 * @returns {React.JSX.Element} The occurrences list component.
 */
function PosterSingleOccurrences({ occurrences, handleSelectOccurrence }) {
  const { t } = useTranslation("common", { keyPrefix: "poster-selector-v2" });

  return (
    <>
      <h5>{t("choose-an-occurrence")}</h5>
      <table className="table table-hover text-left">
        <thead>
          <tr>
            <th scope="col">{t("table-date")}</th>
            <th scope="col">{t("table-price")}</th>
            <th scope="col" aria-label={t("table-actions")} />
          </tr>
        </thead>
        <tbody>
          {occurrences.map(({ entityId, start, ticketPriceRange }) => (
            <tr key={entityId}>
              <td>{formatDate(start)}</td>
              <td>{ticketPriceRange}</td>
              <td>
                <Button onClick={() => handleSelectOccurrence(entityId)}>
                  {t("choose-occurrence")}
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
}

export default PosterSingleOccurrences;
