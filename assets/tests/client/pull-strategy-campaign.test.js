import { describe, it, expect, beforeEach } from "vitest";
import {
  PullStrategy,
  mockGetPath,
  mockGetAllResultsFromPath,
  mockLoadConfig,
  mockLogger,
  PLAYLIST,
  screenPath,
  layoutPath,
  groupsPath,
  campaignsPath,
  slidesPath,
  collection,
  wireApi,
} from "./helpers/pull-strategy-harness.js";

const CAMPAIGN_REGION = "01G112XBWFPY029RYFB8X2H4KD";
const HOUR = 60 * 60 * 1000;
const GROUP = "01JRZ3NDEKTSV4RRFFQ69G5FAV";

/**
 * An ISO timestamp relative to now.
 *
 * @param {number} offset Milliseconds from now.
 * @returns {string} The timestamp.
 */
function at(offset) {
  return new Date(Date.now() + offset).toISOString();
}

/**
 * A campaign row as the campaigns collection returns it.
 *
 * @param {object} published The published window.
 * @returns {object} The row.
 */
function buildCampaign(published) {
  return {
    campaign: {
      "@id": `/v2/playlists/${PLAYLIST}`,
      published,
      schedules: [],
      slides: slidesPath,
    },
  };
}

/**
 * Serve fixed collection responses per path, on top of wireApi.
 *
 * @param {object} responses Responses keyed by path.
 */
function serveCollections(responses) {
  const previousGetAll = mockGetAllResultsFromPath.getMockImplementation();

  mockGetAllResultsFromPath.mockImplementation((path) =>
    Object.prototype.hasOwnProperty.call(responses, path)
      ? Promise.resolve(responses[path])
      : previousGetAll(path),
  );
}

describe("PullStrategy campaign takeover", () => {
  beforeEach(() => {
    mockGetPath.mockReset();
    mockGetAllResultsFromPath.mockReset();
    mockLogger.warn.mockReset();
    mockLoadConfig.mockReset();

    mockLoadConfig.mockResolvedValue({ relationsChecksumEnabled: true });
  });

  it("enters campaign mode for a published screen campaign", async () => {
    wireApi();
    serveCollections({
      [campaignsPath]: collection(campaignsPath, [
        buildCampaign({ from: at(-HOUR), to: at(HOUR) }),
      ]),
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    const data = strategy.lastestScreenData;

    expect(data.hasActiveCampaign).toBe(true);
    expect(data.layoutData.grid).toEqual({ rows: 1, columns: 1 });
    expect(data.layoutData.regions[0]["@id"]).toBe(
      `/v2/layouts/regions/${CAMPAIGN_REGION}`,
    );
    expect(Object.keys(data.regionData)).toEqual([CAMPAIGN_REGION]);
    expect(mockGetPath).not.toHaveBeenCalledWith(layoutPath);
  });

  it("enters campaign mode for a campaign on a screen group", async () => {
    const groupCampaignsPath = `/v2/screen-groups/${GROUP}/campaigns`;

    wireApi();
    serveCollections({
      [groupsPath]: collection(groupsPath, [
        {
          "@id": `/v2/screen-groups/${GROUP}`,
          campaigns: groupCampaignsPath,
        },
      ]),
      [groupCampaignsPath]: collection(groupCampaignsPath, [
        buildCampaign({ from: at(-HOUR), to: at(HOUR) }),
      ]),
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    const data = strategy.lastestScreenData;

    expect(data.hasActiveCampaign).toBe(true);
    expect(data.layoutData.grid).toEqual({ rows: 1, columns: 1 });
    expect(data.layoutData.regions[0]["@id"]).toBe(
      `/v2/layouts/regions/${CAMPAIGN_REGION}`,
    );
    expect(Object.keys(data.regionData)).toEqual([CAMPAIGN_REGION]);
    expect(mockGetPath).not.toHaveBeenCalledWith(layoutPath);
  });
});
