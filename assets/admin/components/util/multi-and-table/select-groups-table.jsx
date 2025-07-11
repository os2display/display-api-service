import { useState, useEffect } from "react";
import PropTypes from "prop-types";
import { useTranslation } from "react-i18next";
import Table from "../table/table";
import { SelectGroupColumns } from "../../groups/groups-columns";
import {
  useGetV2ScreenGroupsQuery,
  useGetV2ScreenGroupsByIdScreensQuery,
} from "../../../redux/api/api.generated.ts";
import GroupsDropdown from "../forms/multiselect-dropdown/groups/groups-dropdown";
import useFetchDataHook from "../../util/fetch-data-hook";
import filterItemFromArray from "../helpers/filter-item-from-array";
import mapToIds from "../helpers/map-to-ids";

/**
 * A multiselect and table for groups.
 *
 * @param {string} props The props.
 * @param {string} props.name The name for the input
 * @param {string} props.id The id used for the get.
 * @param {string} props.getSelectedMethod Method that gets selected for dropdown
 * @param {string | null} props.mappingId For mapping selected data
 * @returns {object} Select groups table.
 */
function SelectGroupsTable({
  handleChange,
  name,
  getSelectedMethod,
  id = "",
  mappingId = null,
}) {
  const { t } = useTranslation("common", { keyPrefix: "select-groups-table" });
  const [selectedData, setSelectedData] = useState([]);
  const [searchText, setSearchText] = useState("");

  // Get 30 groups for dropdown, and when search is changed more will be fetched.
  const { data: groups } = useGetV2ScreenGroupsQuery({
    title: searchText,
    itemsPerPage: 30,
    orderBy: "createdAt",
    order: "asc",
  });

  // Get the selected groups for table below dropdown
  const { data: preSelectedGroups } = useFetchDataHook(getSelectedMethod, [id]);

  /** Map loaded data. */
  useEffect(() => {
    if (preSelectedGroups) {
      let newGroups = preSelectedGroups;
      if (mappingId) {
        newGroups = preSelectedGroups.map((localScreenGroup) => {
          return localScreenGroup[mappingId];
        });
      }
      const value = [...selectedData, ...newGroups];
      setSelectedData(value);
      handleChange({
        target: { id: name, value: value.map((item) => item["@id"]) },
      });
    }
  }, [preSelectedGroups]);

  /**
   * Adds group to list of groups.
   *
   * @param {object} props - The props.
   * @param {object} props.target - The target.
   */
  const handleAdd = ({ target }) => {
    const { value, id: localId } = target;
    setSelectedData(value);
    handleChange({
      target: { id: localId, value: value.map((item) => item["@id"]) },
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
   * Removes group from list of groups.
   *
   * @param {object} removeItem The item to remove.
   */
  const removeFromList = (removeItem) => {
   const filteredSelectedData = filterItemFromArray(selectedData, removeItem);

    setSelectedData(filteredSelectedData);

    handleChange({
      target: {
        value: mapToIds(filteredSelectedData),
        id: name,
      },
    });
  };

  const columns = SelectGroupColumns({
    handleDelete: removeFromList,
    apiCall: useGetV2ScreenGroupsByIdScreensQuery,
    editTarget: "group",
    infoModalRedirect: "/screen/edit",
    infoModalTitle: t("info-modal.screens"),
  });

  if (!groups || !groups["hydra:member"]) return null;

  return (
    <>
      <GroupsDropdown
        name={name}
        data={groups["hydra:member"]}
        handleGroupsSelection={handleAdd}
        selected={selectedData}
        filterCallback={onFilter}
      />
      {selectedData.length > 0 && (
        <>
          <Table columns={columns} data={selectedData} />
          <small>{t("edit-groups-help-text")}</small>
        </>
      )}
    </>
  );
}

SelectGroupsTable.propTypes = {
  name: PropTypes.string.isRequired,
  handleChange: PropTypes.func.isRequired,
  id: PropTypes.string,
  mappingId: PropTypes.string,
  getSelectedMethod: PropTypes.func.isRequired,
};

export default SelectGroupsTable;
