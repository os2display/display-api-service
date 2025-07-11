import { React, useState, useEffect } from "react";
import PropTypes from "prop-types";
import { useTranslation } from "react-i18next";
import Table from "../table/table";
import ScreensDropdown from "../forms/multiselect-dropdown/screens/screens-dropdown";
import { SelectScreenColumns } from "../../screen/util/screen-columns";
import {
  useGetV2ScreensQuery,
  useGetV2ScreensByIdScreenGroupsQuery,
  useGetV2CampaignsByIdScreensQuery,
} from "../../../redux/api/api.generated.ts";
import filterItemFromArray from "../helpers/filter-item-from-array";
import mapToIds from "../helpers/map-to-ids";

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
  const [totalItems, setTotalItems] = useState(0);
  const [page, setPage] = useState(1);
  const [searchText, setSearchText] = useState("");

  // Get 30 screens for dropdown, and when search is changed more will be fetched.
  const { data: screens } = useGetV2ScreensQuery({
    search: searchText,
    itemsPerPage: 30,
    order: { createdAt: "desc" },
  });

  // Get 10 of the selected screens for table below dropdown, table is paginated so on page change more is fetched.
  const { data: alreadySelectedScreens } = useGetV2CampaignsByIdScreensQuery(
    {
      id: campaignId,
      itemsPerPage: 10,
      page,
    },
    { skip: !campaignId }
  );

  useEffect(() => {
    if (alreadySelectedScreens) {
      setTotalItems(alreadySelectedScreens["hydra:totalItems"]);
      const newScreens = alreadySelectedScreens["hydra:member"].map(
        ({ screen }) => screen
      );
      setSelectedData([...selectedData, ...newScreens]);
    }
  }, [alreadySelectedScreens]);

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
