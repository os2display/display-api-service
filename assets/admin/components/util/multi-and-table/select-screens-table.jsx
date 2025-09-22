import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import Table from "../table/table";
import ScreensDropdown from "../multiselect-dropdown/screens/screens-dropdown";
import { SelectScreenColumns } from "../../screen/util/screen-columns";
import {
  enhancedApi,
  useGetV2ScreensQuery,
  useGetV2ScreensByIdScreenGroupsQuery,
} from "../../../../shared/redux/enhanced-api.ts";
import useFetchDataHook from "../fetch-data-hook.js";

/**
 * A multiselect and table for screens.
 *
 * @param {string} props - The props.
 * @param {Function} props.handleChange - The callback on change.
 * @param {string} props.name - The name for the input
 * @param {string} props.campaignId - The campaign id.
 * @returns {object} - A select screens table.
 */
function SelectScreensTable({ handleChange, name, campaignId = "" }) {
  const { t } = useTranslation("common", { keyPrefix: "select-screens-table" });
  const [selectedData, setSelectedData] = useState([]);
  const [searchText, setSearchText] = useState("");

  // Get 30 screens for dropdown, and when search is changed more will be fetched.
  const { data: screens } = useGetV2ScreensQuery({
    search: searchText,
    itemsPerPage: 30,
    order: { createdAt: "desc" },
  });

  // Get the selected screens for table below dropdown
  const { data: preSelectedScreens } = useFetchDataHook(
    enhancedApi.endpoints.getV2CampaignsByIdScreens.initiate,
    [campaignId],
  );

  useEffect(() => {
    if (preSelectedScreens) {
      const newScreens = preSelectedScreens.map(({ screen }) => screen);
      setSelectedData([...selectedData, ...newScreens]);
    }
  }, [preSelectedScreens]);

  /**
   * Adds group to list of groups.
   *
   * @param {object} props - The props.
   * @param {object} props.target - The target.
   */
  const handleAdd = ({ target }) => {
    const { value, id } = target;
    setSelectedData(value);
    handleChange({
      target: { id, value: value.map((item) => item["@id"]) },
    });
  };

  /**
   * Fetches data for the multi component
   *
   * @param {string} filter - The filter.
   */
  const onFilter = (filter) => {
    setSearchText(filter);
  };

  /**
   * Removes playlist from list of groups.
   *
   * @param {string} removeItem The item to remove.
   */
  const removeFromList = (removeItem) => {
    const indexOfItemToRemove = selectedData
      .map((item) => {
        return item["@id"];
      })
      .indexOf(removeItem);
    const selectedDataCopy = [...selectedData];
    selectedDataCopy.splice(indexOfItemToRemove, 1);
    setSelectedData(selectedDataCopy);

    const target = {
      value: selectedDataCopy.map((item) => item["@id"]),
      id: name,
    };
    handleChange({ target });
  };

  // The columns for the table.
  const columns = SelectScreenColumns({
    handleDelete: removeFromList,
    apiCall: useGetV2ScreensByIdScreenGroupsQuery,
    editTarget: "screen",
    infoModalRedirect: "/group/edit",
    infoModalTitle: t("info-modal.screen-in-groups"),
  });

  return (
    <>
      {screens && screens["hydra:member"] && (
        <>
          <ScreensDropdown
            name={name}
            handleScreenSelection={handleAdd}
            selected={selectedData}
            data={screens["hydra:member"]}
            filterCallback={onFilter}
          />
          {selectedData?.length > 0 && (
            <>
              <Table columns={columns} data={selectedData} />
              <small>{t("edit-screens-help-text")}</small>
            </>
          )}
        </>
      )}
    </>
  );
}

export default SelectScreensTable;
