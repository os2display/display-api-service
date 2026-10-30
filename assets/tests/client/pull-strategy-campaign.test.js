import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import {
  PullStrategy,
  mockGetPath,
  mockGetAllResultsFromPath,
  mockLoadConfig,
  mockLogger,
  REGION,
  PLAYLIST,
  screenPath,
  layoutPath,
  groupsPath,
  campaignsPath,
  regionPath,
  slidesPath,
  collection,
  wireApi,
  callsFor,
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

  afterEach(() => vi.useRealTimers());

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
    expect(data.regionData[CAMPAIGN_REGION][0].slidesData).toHaveLength(1);
    // Normal regions are not fetched either.
    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(0);
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
    expect(data.regionData[CAMPAIGN_REGION][0].slidesData).toHaveLength(1);
    // Normal regions are not fetched either.
    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(0);
    expect(mockGetPath).not.toHaveBeenCalledWith(layoutPath);
  });

  it("stays in normal mode when every campaign is outside its published window", async () => {
    wireApi();
    serveCollections({
      [campaignsPath]: collection(campaignsPath, [
        buildCampaign({ from: at(-2 * HOUR), to: at(-HOUR) }),
        buildCampaign({ from: at(HOUR), to: at(2 * HOUR) }),
      ]),
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    const data = strategy.lastestScreenData;

    expect(data.hasActiveCampaign).toBe(false);
    expect(mockGetPath).toHaveBeenCalledWith(layoutPath);
    expect(Object.keys(data.regionData)).toEqual([REGION]);
  });

  it("leaves campaign mode when the campaign expires between two pulls, with unchanged checksums", async () => {
    // Complements cache-recovery's checksum-moved case; here only the clock
    // moves.
    vi.useFakeTimers({ toFake: ["Date"] });
    vi.setSystemTime(new Date("2026-10-23T12:00:00.000Z"));

    wireApi();
    serveCollections({
      [campaignsPath]: collection(campaignsPath, [
        buildCampaign({ from: at(-HOUR), to: at(HOUR) }),
      ]),
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    expect(strategy.lastestScreenData.hasActiveCampaign).toBe(true);

    vi.setSystemTime(Date.now() + 2 * HOUR);

    await strategy.getScreen(screenPath);

    expect(strategy.lastestScreenData.hasActiveCampaign).toBe(false);
    // The campaigns came from the cache.
    expect(callsFor(mockGetAllResultsFromPath, campaignsPath)).toBe(1);
    expect(callsFor(mockGetPath, layoutPath)).toBe(1);
    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(1);
    expect(Object.keys(strategy.lastestScreenData.regionData)).toEqual([
      REGION,
    ]);
  });

  it("keeps normal content when the campaign fetch fails", async () => {
    // The campaigns checksum is not stored, so the next pull retries rather
    // than trusting an empty list (2.x stored it and waited for a change).
    wireApi();
    // getAllResultsFromPath answers a bare {} when a page failed.
    serveCollections({ [campaignsPath]: {} });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    expect(strategy.lastestScreenData.hasActiveCampaign).toBe(false);
    expect(typeof strategy.lastestScreenChecksums.campaigns).toBe("symbol");
    expect(mockGetPath).toHaveBeenCalledWith(layoutPath);
  });
});
