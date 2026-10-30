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
 * @param {Array} props.initialOptions Stations offered before searching, e.g.
 *   the stations of a slide created before the feed existed.
 * @returns {object} Station selector.
 */
function StationSelector({
  onChange,
  name,
  optionsEndpoint,
  helpText = "",
  label,
  value: inputValue,
  initialOptions = [],
}) {
  const { t } = useTranslation("common", { keyPrefix: "station-selector" });
  const [data, setData] = useState([]);
  const [searchText, setSearchText] = useState("");
  const [loading, setLoading] = useState(false);
  // Captured once: upgrading an old slide clears its content stations as soon
  // as the first feed station is picked, and the rest should stay on offer.
  const [startOptions] = useState(() =>
    Array.isArray(initialOptions)
      ? initialOptions.filter((station) => station?.id && station?.name)
      : [],
  );

  const handleSelect = ({ target }) => {
    const { value, id: localId } = target;
    onChange({
      target: { id: localId, value },
    });
  };

  useEffect(() => {
    // The api does not accept empty string as input.
    if (!optionsEndpoint || searchText === "") {
      setLoading(false);
      return undefined;
    }

    // Shown right away, not after the debounce, so typing gives feedback.
    setLoading(true);

    // Aborting on cleanup stops a slow, older response from overwriting a
    // newer one.
    const controller = new AbortController();

    const timeout = setTimeout(() => {
      fetch(
        `${optionsEndpoint}?${new URLSearchParams({ search: searchText })}`,
        {
          headers: getHeaders(),
          signal: controller.signal,
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
          setLoading(false);
        })
        .catch((er) => {
          // An aborted request was replaced by a newer search, which is
          // still loading.
          if (er.name !== "AbortError") {
            setLoading(false);
            displayError(t("get-error"), er);
          }
        });
    }, SEARCH_DEBOUNCE_MS);

    return () => {
      clearTimeout(timeout);
      controller.abort();
    };
  }, [searchText, optionsEndpoint]);

  const getNoOptionsText = () => {
    if (loading) {
      return t("searching");
    }

    return searchText === "" ? t("type-to-search") : t("no-results");
  };

  return (
    <div className="mb-3" id="station-selector">
      <MultiSelectComponent
        options={searchText === "" ? startOptions : data}
        handleSelection={handleSelect}
        name={name}
        selected={inputValue || []}
        filterCallback={setSearchText}
        label={label}
        isLoading={loading}
        // Rejseplanen already matched the search, and its fuzzy matches (e.g.
        // "Aa") need not contain the typed text.
        disableLocalFilter
        noSelectedString={t("nothing-selected")}
        searchPlaceholder={t("search-placeholder")}
        noOptionsText={getNoOptionsText()}
      />
      <small>{helpText}</small>
    </div>
  );
}

export default StationSelector;
