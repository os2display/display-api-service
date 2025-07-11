import { useState, useEffect } from "react";
import PropTypes from "prop-types";
import { useTranslation } from "react-i18next";
import Table from "../table/table";
import ScreensDropdown from "../forms/multiselect-dropdown/screens/screens-dropdown";
import { SelectScreenColumns } from "../../screen/util/screen-columns";
import {
  api,
  useGetV2ScreensQuery,
  useGetV2ScreensByIdScreenGroupsQuery,
} from "../../../redux/api/api.generated.ts";
import useFetchAllItems from "../../util/fetchAllItemsHook";
import mapToIds from "../helpers/map-to-ids";
import filterItemFromArray from "../helpers/filter-item-from-array";

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
  const { data: preSelectedScreens } = useFetchAllItems(
    api.endpoints.getV2CampaignsByIdScreens.initiate,
    [campaignId]
  );

  useEffect(() => {
    if (preSelectedScreens) {
      const newScreens = preSelectedScreens.map(
        ({ screen }) => screen
      );
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
   * Removes screen from list of screens.
   *
   * @param {string} removeItem The item to remove.
   */
  const removeFromList = (removeItem) => {
    const filteredSelectedData = filterItemFromArray(selectedData,removeItem);
    
    setSelectedData(filteredSelectedData);

    handleChange({
      target: {
        value: mapToIds(filteredSelectedData),
        id: name,
      },
    });
  };

  // The columns for the table.
  const columns = SelectScreenColumns({
    handleDelete: removeFromList,
    apiCall: useGetV2ScreensByIdScreenGroupsQuery,
    editTarget: "screen",
    infoModalRedirect: "/group/edit",
    infoModalTitle: t("info-modal.screen-in-groups"),
  });

  if (!screens || !screens["hydra:member"]) return null;

  return (
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
  );
}

SelectScreensTable.propTypes = {
  name: PropTypes.string.isRequired,
  handleChange: PropTypes.func.isRequired,
  campaignId: PropTypes.string,
};

export default SelectScreensTable;
