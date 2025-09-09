import { useState, useEffect } from "react";
import { Tabs, Tab, Alert } from "react-bootstrap";
import Grid from "./grid";
import { useTranslation } from "react-i18next";
import idFromUrl from "../../util/helpers/id-from-url";
import PlaylistDragAndDrop from "../../playlist-drag-and-drop/playlist-drag-and-drop";
import { enhancedApi } from "../../../../shared/redux/enhanced-api.ts";
import useFetchDataHook from "../../util/fetch-data-hook.js";
import mapToIds from "../../util/helpers/map-to-ids.js";
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
    regions.length > 0 ? regions[0]["@id"] : "",
  );
  const [selectedPlaylists, setSelectedPlaylists] = useState([]);
  const { data: playlistsAndRegions } = useFetchDataHook(
    enhancedApi.endpoints.getV2ScreensByIdRegionsAndRegionIdPlaylists.initiate,
    mapToIds(regions), // returns and array with ids to fetch for all ids
    {
      id: screenId, // screen id is the id
    },
    "regionId", // The key for the list of ids
  );

  /**
   * @param {object} props The props
   * @param {Array} props.value The value
   * @param {string} props.id The id
   * @returns {Array} Mapped data
   */
  function mapData({ value: inputPlaylists, id }) {
    // Map to add region id to incoming data.
    const localTarget = inputPlaylists.map((playlist) => {
      return {
        region: idFromUrl(id),
        ...playlist,
      };
    });
    // A copy, to be able to remove items.
    let selectedPlaylistsCopy = [...selectedPlaylists];

    // The following is used to determine if something has been removed from a list.
    const regionPlaylists = selectedPlaylists
      .filter(({ region }) => region === id)
      .map(({ region }) => region);

    const selectedWithoutRegion = [];

    // Checks if an element has been removed from the list
    if (inputPlaylists.length < regionPlaylists.length) {
      selectedPlaylists.forEach((playlist) => {
        if (!regionPlaylists.includes(playlist.region)) {
          selectedWithoutRegion.push(playlist);
        }
      });
      //  If a playlist is removed from a list, all the playlists in that region will be removed.
      selectedPlaylistsCopy = selectedWithoutRegion;
    }

    // Removes duplicates.
    const localSelectedPlaylists = [
      ...localTarget,
      ...selectedPlaylistsCopy,
    ].filter(
      (playlist, index, self) =>
        index ===
        self.findIndex(
          (secondPlaylist) =>
            secondPlaylist["@id"] === playlist["@id"] &&
            secondPlaylist.region === playlist.region,
        ),
    );

    return localSelectedPlaylists;
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
   * Removes playlist from list of playlists.
   *
   * @param {object} inputPlaylistId - InputPlaylistId to remove
   * @param {object} inputRegionId - InputRegionId to remove from
   */
  const removeFromList = (inputPlaylistId, inputRegionId) => {
    setSelectedPlaylists((prev) =>
      prev.filter(
        ({ "@id": id, region: regionId }) =>
          !(regionId === inputRegionId && id === inputPlaylistId),
      ),
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
            <Tab eventKey={id} key={id} title={title}>
              <PlaylistDragAndDrop
                id="playlist_drag_and_drop"
                handleChange={handleChange}
                removeFromList={removeFromList}
                name={id}
                regionIdForInitializeCallback={id}
                screenId={screenId}
                regionId={idFromUrl(id)}
                selectedPlaylists={selectedPlaylists.filter(
                  ({ region }) => region === idFromUrl(id),
                )}
              />
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

export default GridGenerationAndSelect;
