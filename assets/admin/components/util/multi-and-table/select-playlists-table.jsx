import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import Table from "../table/table";
import {
  enhancedApi,
  useGetV2PlaylistsQuery,
  useGetV2PlaylistsByIdSlidesQuery,
} from "../../../../shared/redux/enhanced-api.ts";
import PlaylistsDropdown from "../forms/multiselect-dropdown/playlists/playlists-dropdown";
import { SelectPlaylistColumns } from "../../playlist/playlists-columns";
import useFetchDataHook from "../fetch-data-hook.js";

/**
 * A multiselect and table for groups.
 *
 * @param {string} props The props.
 * @param {string} props.name The name for the input
 * @param {string} props.id The id used for the get.
 * @param {string} props.helpText Helptext for dropdown.
 * @returns {object} Select groups table.
 */
function SelectPlaylistsTable({ handleChange, name, id = "", helpText }) {
  const { t } = useTranslation("common", {
    keyPrefix: "select-playlists-table",
  });
  const [selectedData, setSelectedData] = useState([]);
  const [searchText, setSearchText] = useState("");

  // Get 30 playlists for dropdown, and when search is changed more will be fetched.
  const { data: playlists } = useGetV2PlaylistsQuery({
    title: searchText,
    itemsPerPage: 30,
    isCampaign: false,
    sharedWithMe: false,
    order: { createdAt: "desc" },
  });

  // Get the selected playlists for table below dropdown
  const { data: preSelectedPlaylists } = useFetchDataHook(
    enhancedApi.endpoints.getV2SlidesByIdPlaylists.initiate,
    [id],
  );

  /** Map loaded data. */

  useEffect(() => {
    if (preSelectedPlaylists) {
      const newPlaylists = preSelectedPlaylists.map(({ playlist }) => {
        return playlist;
      });
      setSelectedData([...selectedData, ...newPlaylists]);
    }
  }, [preSelectedPlaylists]);

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
   * Removes playlist from list of groups.
   *
   * @param {object} removeItem The item to remove.
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
  const columns = SelectPlaylistColumns({
    handleDelete: removeFromList,
    apiCall: useGetV2PlaylistsByIdSlidesQuery,
    editTarget: "playlist",
    infoModalRedirect: "/slide/edit",
    dataKey: "slide",
    infoModalTitle: t("info-modal.slides"),
  });

  return (
    <>
      {playlists && (
        <>
          <PlaylistsDropdown
            name={name}
            data={playlists["hydra:member"]}
            handlePlaylistSelection={handleAdd}
            selected={selectedData}
            filterCallback={onFilter}
            helpText={helpText}
          />
          {selectedData.length > 0 && (
            <Table columns={columns} data={selectedData} />
          )}
        </>
      )}
    </>
  );
}

export default SelectPlaylistsTable;
