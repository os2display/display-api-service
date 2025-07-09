import { useState, useEffect } from "react";
import PropTypes from "prop-types";
import { Tabs, Tab, Alert, Spinner } from "react-bootstrap";
import Grid from "./grid";
import { useTranslation } from "react-i18next";
import idFromUrl from "../../util/helpers/id-from-url";
import PlaylistDragAndDrop from "../../playlist-drag-and-drop/playlist-drag-and-drop";
import { api } from "../../../redux/api/api.generated.ts";
import useFetchDataHook from "../../util/fetch-data-hook";
import "./grid.scss";

/**
 * The grid generator component.
 *
 * @param {object} props Props.
 * @param {object} props.grid The grid to generate.
 * @param {object} props.regions The regions in the grid.
 * @param {boolean} props.vertical True if the screen is vertical
 * @param {Function} props.handleInput - A callback on select in multiselect
 * @param {string} props.screenId - A screen id
 * @returns {object} The component.
 */
function GridGenerationAndSelect({
  grid,
  vertical,
  handleInput,
  screenId,
  regions = [],
}) {
  const { t } = useTranslation("common");
  const [selectedRegion, setSelectedRegion] = useState(
    regions.length > 0 ? regions[0]["@id"] : ""
  );
  const [selectedPlaylists, setSelectedPlaylists] = useState([]);

  // Todo, error handling
  const { data: playlistsAndRegions, loading } = useFetchDataHook(
    api.endpoints.getV2ScreensByIdRegionsAndRegionIdPlaylists.initiate,
    mapToIds(regions), // returns and array with ids to fetch for all ids
    {
      id: screenId, // screen id is the id
    },
    "regionId" // The key for the list of ids
  );

  /**
   * @param {object} props The props
   * @param {Array} props.value The value
   * @param {string} props.id The id
   * @returns {Array} Mapped data
   */
  function mapData({ value: inputPlaylists, id }) {
    // Region id form id url
    const region = idFromUrl(id);

    // Add the region id to each inputted playlist
    const playlistsWithRegion = inputPlaylists.map((playlist) => ({
      region,
      ...playlist,
    }));

    // Get the playlists that belong the same region from the selected playlists
    const existingRegionPlaylists = selectedPlaylists.filter(
      (playlist) => playlist.region === region
    );

    // Check if any playlists from the existing region playlists are missing from
    // The inputted playlists if so, they are removed from the list
    const removedPlaylists = existingRegionPlaylists.some(
      ({ "@id": existingId }) =>
        !inputPlaylists.find(
          ({ "@id": incomingId }) => incomingId === existingId
        )
    );

    // Start with the existing selected playlists
    let updatedRegionPlaylists = [...selectedPlaylists];

    // If any playlists were removed, filter out all playlists for this region
    if (removedPlaylists) {
      updatedRegionPlaylists = selectedPlaylists.filter(
        (playlist) => playlist.region !== region
      );
    }

    // Merge the updated region playlists with the input playlists,
    // and remove any duplicate region and id combinations
    const mappedData = [
      ...playlistsWithRegion,
      ...updatedRegionPlaylists,
    ].filter(
      (playlist, index, self) =>
        index ===
        self.findIndex(
          ({ region, "@id": playlistId }) =>
            playlistId === playlist["@id"] && region === playlist.region
        )
    );

    return mappedData;
  }

  // On received data, map to fit the components
  // We need region id to figure out which dropdown they should be placed in
  // and weight (order) for sorting.
  useEffect(() => {
    if (playlistsAndRegions && playlistsAndRegions.length > 0) {
      const playlists = playlistsAndRegions
        .map(({ originalArgs: { regionId }, playlist, weight }) => ({
          ...playlist,
          weight,
          region: regionId,
        }))
        .sort((a, b) => a.weight - b.weight);

      setSelectedPlaylists(playlists);
    }
  }, [playlistsAndRegions]);

  useEffect(() => {
    handleInput({ target: { value: selectedPlaylists, id: "playlists" } });
  }, [selectedPlaylists]);

  /**
   * @param {object} props The props.
   * @param {object} props.target Event target
   */
  const handleChange = ({ target }) => {
    const playlists = mapData(target);
    setSelectedPlaylists(playlists);
  };

  /**
   * Removes playlist from list of playlists, and closes modal.
   *
   * @param {object} inputPlaylistId - InputPlaylistId to remove
   * @param {object} inputRegionId - InputRegionId to remove from
   */
  const removeFromList = (inputPlaylistId, inputRegionId) => {
    setSelectedPlaylists((prev) =>
      prev.filter(
        ({ "@id": id, region: regionId }) =>
          !(regionId === inputRegionId && id === inputPlaylistId)
      )
    );
  };

  // If there are no regions, the components should not spend time rendering.
  if (regions?.length === 0) return null;

  return (
    <>
      <div className="col-md-4 my-3 my-md-0">
        <div className="bg-light border rounded p-1">
          <Grid
            grid={grid}
            vertical={vertical}
            regions={regions}
            selected={selectedRegion}
          />
        </div>
      </div>
      <div className="col-md-12">
        <h3 className="h5">{t("screen-form.screen-region-playlists")}</h3>
        <Tabs
          defaultActiveKey={regions[0]["@id"]}
          id="tabs"
          onSelect={setSelectedRegion}
          className="mb-3"
        >
          {regions.map(({ title, "@id": id, type }) => (
            <Tab eventKey={data["@id"]} key={id} title={title}>
              {loading && (
                <Spinner animation="border" className="loading-spinner" />
              )}
              {!loading && (
                <PlaylistDragAndDrop
                  id="playlist_drag_and_drop"
                  handleChange={handleChange}
                  removeFromList={removeFromList}
                  name={id}
                  regionIdForInitializeCallback={id}
                  screenId={screenId}
                  regionId={idFromUrl(id)}
                  selectedPlaylists={selectedPlaylists.filter(
                    ({ region }) => region === idFromUrl(id)
                  )}
                />
              )}
              {type === "touch-buttons" && (
                <Alert key="screen-form-touch-buttons" variant="info">
                  {t("screen-form.touch-region-helptext")}
                </Alert>
              )}
            </Tab>
          ))}
        </Tabs>
      </div>
    </>
  );
}

GridGenerationAndSelect.propTypes = {
  grid: PropTypes.shape({ columns: PropTypes.number, rows: PropTypes.number })
    .isRequired,
  screenId: PropTypes.string.isRequired,
  vertical: PropTypes.bool.isRequired,
  handleInput: PropTypes.func.isRequired,
  regions: PropTypes.arrayOf(PropTypes.shape(PropTypes.any)),
};

export default GridGenerationAndSelect;
