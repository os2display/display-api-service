import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import MultiSelectComponent from "../../../util/forms/multiselect-dropdown/multi-dropdown";
import { displayError } from "../../../util/list/toast-component/display-toast";
import { getHeaders } from "../poster/poster-helper";

const SEARCH_DEBOUNCE_MS = 300;

/**
 * A multiselect for Rejseplanen stations.
 *
 * Searches through the feed source config endpoint, so the Rejseplanen API
 * key stays on the server.
 *
 * @param {object} props The props.
 * @param {string} props.name The name for the input
 * @param {string} props.optionsEndpoint Feed source config endpoint to search.
 * @param {string} props.helpText Help text for dropdown.
 * @param {string} props.label The label.
 * @param {Function} props.onChange On change callback.
 * @param {Array} props.value Input value.
 * @returns {object} Station selector.
 */
function StationSelector({
  onChange,
  name,
  optionsEndpoint,
  helpText = "",
  label,
  value: inputValue,
}) {
  const { t } = useTranslation("common", { keyPrefix: "station-selector" });
  const [data, setData] = useState([]);
  const [searchText, setSearchText] = useState("");

  const handleSelect = ({ target }) => {
    const { value, id: localId } = target;
    onChange({
      target: { id: localId, value },
    });
  };

  useEffect(() => {
    // The api does not accept empty string as input.
    if (!optionsEndpoint || searchText === "") {
      return undefined;
    }

    const timeout = setTimeout(() => {
      fetch(
        `${optionsEndpoint}?${new URLSearchParams({ search: searchText })}`,
        {
          headers: getHeaders(),
        },
      )
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          return response.json();
        })
        .then((stations) => {
          setData(Array.isArray(stations) ? stations : []);
        })
        .catch((er) => {
          displayError(t("get-error"), er);
        });
    }, SEARCH_DEBOUNCE_MS);

    return () => clearTimeout(timeout);
  }, [searchText, optionsEndpoint]);

  return (
    <div className="mb-3" id="station-selector">
      <MultiSelectComponent
        options={data}
        handleSelection={handleSelect}
        name={name}
        selected={inputValue || []}
        filterCallback={setSearchText}
        label={label}
      />
      <small>{helpText}</small>
    </div>
  );
}

export default StationSelector;
