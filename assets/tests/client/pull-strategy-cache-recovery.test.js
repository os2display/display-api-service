import { describe, it, expect, vi, beforeEach } from "vitest";

const { mockGetPath, mockGetAllResultsFromPath, mockLoadConfig, mockLogger } =
  vi.hoisted(() => ({
    mockGetPath: vi.fn(),
    mockGetAllResultsFromPath: vi.fn(),
    mockLoadConfig: vi.fn(),
    mockLogger: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() },
  }));

vi.mock("../../client/logger/logger", () => ({ default: mockLogger }));

vi.mock("../../client/util/client-config-loader.js", () => ({
  default: { loadConfig: mockLoadConfig },
}));

vi.mock("../../client/data-sync/api-helper", () => ({
  default: vi.fn().mockImplementation(function () {
    this.getPath = mockGetPath;
    this.getAllResultsFromPath = mockGetAllResultsFromPath;
  }),
}));

import PullStrategy from "../../client/data-sync/pull-strategy";

const SCREEN = "01ARZ3NDEKTSV4RRFFQ69G5FAV";
const REGION = "01BRZ3NDEKTSV4RRFFQ69G5FAV";
const PLAYLIST = "01CRZ3NDEKTSV4RRFFQ69G5FAV";
const SLIDE = "01DRZ3NDEKTSV4RRFFQ69G5FAV";
const TEMPLATE = "01FRZ3NDEKTSV4RRFFQ69G5FAV";

const screenPath = `/v2/screens/${SCREEN}`;
const layoutPath = `/v2/layouts/01ERZ3NDEKTSV4RRFFQ69G5FAV`;
const groupsPath = `${screenPath}/screen-groups`;
const campaignsPath = `${screenPath}/campaigns`;
const regionPath = `${screenPath}/regions/${REGION}/playlists`;
const slidesPath = `/v2/playlists/${PLAYLIST}/slides`;
const templatePath = `/v2/templates/${TEMPLATE}`;
const mediaPath = `/v2/media/01GRZ3NDEKTSV4RRFFQ69G5FAV`;

/**
 * Build a screen as the API would return it.
 *
 * @param {object|null} relationsChecksum Checksums to advertise.
 * @returns {object} The screen.
 */
function buildScreen(relationsChecksum) {
  const screen = {
    "@id": screenPath,
    layout: layoutPath,
    regions: [regionPath],
    campaigns: campaignsPath,
    inScreenGroups: groupsPath,
  };

  if (relationsChecksum !== null) {
    screen.relationsChecksum = relationsChecksum;
  }

  return screen;
}

const checksums = {
  campaigns: "campaigns-1",
  layout: "layout-1",
  regions: "regions-1",
  inScreenGroups: "groups-1",
};

/**
 * Build a slide, optionally referencing a media item.
 *
 * @param {Array} media Media ids the slide uses.
 * @returns {object} The slide.
 */
function buildSlide(media = []) {
  return {
    "@id": `/v2/slides/${SLIDE}`,
    relationsChecksum: { templateInfo: "template-1", media: "media-1" },
    templateInfo: { "@id": templatePath },
    media,
  };
}

/**
 * A successful paginated collection response.
 *
 * @param {string} path The path that was requested.
 * @param {Array} results The rows.
 * @returns {object} The response.
 */
function collection(path, results) {
  return { path, results, keys: {} };
}

describe("PullStrategy recovers from a degraded pull", () => {
  beforeEach(() => {
    mockGetPath.mockReset();
    mockGetAllResultsFromPath.mockReset();
    mockLogger.warn.mockReset();
    mockLoadConfig.mockReset();

    // The client config omits relationsChecksumEnabled when it cannot be
    // loaded, and `undefined !== false` leaves caching on, so the tests that
    // matter here are the ones with it explicitly enabled.
    mockLoadConfig.mockResolvedValue({ relationsChecksumEnabled: true });
  });

  /**
   * Wire up the api helper mocks.
   *
   * @param {object} options Per-path overrides.
   */
  function wireApi({
    screen = buildScreen(checksums),
    slide = buildSlide(),
    regionResponses = null,
    mediaResponses = null,
    slidesResponses = null,
  } = {}) {
    const regionQueue = regionResponses ? [...regionResponses] : null;
    const mediaQueue = mediaResponses ? [...mediaResponses] : null;
    const slidesQueue = slidesResponses ? [...slidesResponses] : null;

    mockGetPath.mockImplementation((path) => {
      if (path === screenPath) {
        return Promise.resolve(screen);
      }

      if (path === layoutPath) {
        return Promise.resolve({ grid: { rows: 1, columns: 1 }, regions: [] });
      }

      if (path === mediaPath) {
        return Promise.resolve(
          mediaQueue && mediaQueue.length > 0
            ? mediaQueue.shift()
            : { assets: {} },
        );
      }

      return Promise.resolve(null);
    });

    mockGetAllResultsFromPath.mockImplementation((path) => {
      if (path === groupsPath || path === campaignsPath) {
        return Promise.resolve(collection(path, []));
      }

      if (path === regionPath) {
        if (regionQueue && regionQueue.length > 0) {
          return Promise.resolve(regionQueue.shift());
        }

        return Promise.resolve(
          collection(path, [
            {
              playlist: {
                "@id": `/v2/playlists/${PLAYLIST}`,
                slides: slidesPath,
              },
            },
          ]),
        );
      }

      if (path === slidesPath) {
        if (slidesQueue && slidesQueue.length > 0) {
          return Promise.resolve(slidesQueue.shift());
        }

        return Promise.resolve(collection(path, [{ slide }]));
      }

      return Promise.resolve({});
    });
  }

  /**
   * Number of times a path was requested.
   *
   * @param {object} mock The mock to inspect.
   * @param {string} path The path to count.
   * @returns {number} Call count.
   */
  function callsFor(mock, path) {
    return mock.mock.calls.filter(([called]) => called === path).length;
  }

  it("refetches a region whose playlists failed, even though the checksum is unchanged", async () => {
    // The whole point of the fix: a pull that fell back to cached data must not
    // be credited with the server's checksum, or the next pull compares equal,
    // takes the cache branch and the region stays stale until an editor happens
    // to change the content (#507).
    wireApi({ regionResponses: [{}] });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(2);
    expect(mockLogger.warn).toHaveBeenCalled();
  });

  it("still serves regions from cache when the pull was clean", async () => {
    // The counterpart to the test above: the fix must not simply disable
    // checksum caching.
    wireApi();

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(1);
  });

  it("refetches the layout after a failed layout request", async () => {
    wireApi();

    mockGetPath.mockImplementation((path) => {
      if (path === screenPath) {
        return Promise.resolve(buildScreen(checksums));
      }

      if (path === layoutPath) {
        return Promise.resolve(null);
      }

      return Promise.resolve({ resources: {} });
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetPath, layoutPath)).toBe(2);
  });

  it("never caches a screen that advertises no checksums", async () => {
    // The API really can send nothing here: the DTO getter answers null for an
    // empty map. Two empty maps compare equal on every key, so defaulting to {}
    // would freeze the screen after the first pull.
    wireApi({ screen: buildScreen(null) });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetAllResultsFromPath, regionPath)).toBe(2);
  });

  it("reads the template off the slide instead of requesting it", async () => {
    // Templates are bundled into the client build, and the only thing rendering
    // wants from templateData is the id it looks the bundled module up by. That
    // id is already in the slide's template IRI, so the request the pull used to
    // make bought nothing and cost a region whenever it was throttled.
    wireApi();

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetPath, templatePath)).toBe(0);

    const slide =
      strategy.lastestScreenData.regionData[REGION][0].slidesData[0];

    expect(slide.templateData).toEqual({ id: TEMPLATE });
    expect(slide.invalid).toBeUndefined();
  });

  it("marks the slide invalid when its template IRI carries no id", async () => {
    // Region drops invalid slides. Nothing can be rendered without an id to
    // resolve the template module by, so this is the one case left where a
    // slide has to be held back.
    const slide = buildSlide();
    slide.templateInfo = { "@id": "/v2/templates/" };

    wireApi({ slide });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    const pulled =
      strategy.lastestScreenData.regionData[REGION][0].slidesData[0];

    expect(pulled.templateData).toBeNull();
    expect(pulled.invalid).toBe(true);
    expect(mockLogger.warn).toHaveBeenCalled();
  });

  it("keeps the previously loaded slides when a playlist's slides request fails", async () => {
    // getRegions hands back playlists straight off the API, which have never
    // carried slidesData, so cloneDeep has nothing to preserve here. Without a
    // lookup the playlist empties for a whole pull even though the region
    // itself loaded fine.
    mockLoadConfig.mockResolvedValue({ relationsChecksumEnabled: false });

    wireApi({
      slidesResponses: [
        collection(slidesPath, [{ slide: buildSlide() }]),
        // getAllResultsFromPath answers a bare {} when a page failed.
        {},
      ],
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    const { slidesData } = strategy.lastestScreenData.regionData[REGION][0];

    expect(slidesData).toHaveLength(1);
    expect(slidesData[0]["@id"]).toBe(`/v2/slides/${SLIDE}`);
  });

  it("does not hand a new playlist the slides of the one it replaced", async () => {
    // The reason the lookup matches on @id: a playlist that merely sits at the
    // same position is a different playlist, and showing its predecessor's
    // slides is worse than showing none.
    mockLoadConfig.mockResolvedValue({ relationsChecksumEnabled: false });

    const replacementSlides = "/v2/playlists/replacement/slides";

    wireApi({
      regionResponses: [
        collection(regionPath, [
          {
            playlist: {
              "@id": `/v2/playlists/${PLAYLIST}`,
              slides: slidesPath,
            },
          },
        ]),
        collection(regionPath, [
          {
            playlist: {
              "@id": "/v2/playlists/replacement",
              slides: replacementSlides,
            },
          },
        ]),
      ],
    });

    const previousGetAll = mockGetAllResultsFromPath.getMockImplementation();

    mockGetAllResultsFromPath.mockImplementation((path) => {
      if (path === replacementSlides) {
        return Promise.resolve({});
      }

      return previousGetAll(path);
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    const playlist = strategy.lastestScreenData.regionData[REGION][0];

    expect(playlist["@id"]).toBe("/v2/playlists/replacement");
    expect(playlist.slidesData).toBeUndefined();
  });

  it("clears an invalid flag carried over from an earlier pull", async () => {
    // Region drops invalid slides, so a flag that outlives the reason for it
    // keeps the slide off screen forever. The pull now reads the template off
    // every slide it sees, including the ones it takes from the cache.
    wireApi();

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);

    strategy.lastestScreenData.regionData[REGION][0].slidesData[0].invalid =
      true;

    await strategy.getScreen(screenPath);

    const slide =
      strategy.lastestScreenData.regionData[REGION][0].slidesData[0];

    expect(slide.templateData).toEqual({ id: TEMPLATE });
    expect(slide.invalid).toBeUndefined();
  });

  it("refetches media that failed, rather than reusing the cached null", async () => {
    wireApi({ slide: buildSlide([mediaPath]), mediaResponses: [null] });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetPath, mediaPath)).toBe(2);

    const slide =
      strategy.lastestScreenData.regionData[REGION][0].slidesData[0];

    expect(slide.mediaData[mediaPath]).toEqual({ assets: {} });
  });

  /**
   * Serve a queue of responses per path, falling back to the last entry.
   *
   * @param {object} queues Screens and layouts, in pull order.
   */
  function wireLayoutQueue({ screens, layouts }) {
    const screenQueue = [...screens];
    const layoutQueue = [...layouts];

    mockGetPath.mockImplementation((path) => {
      if (path === screenPath) {
        return Promise.resolve(
          screenQueue.length > 1 ? screenQueue.shift() : screenQueue[0],
        );
      }

      if (path.startsWith("/v2/layouts/")) {
        return Promise.resolve(
          layoutQueue.length > 1 ? layoutQueue.shift() : layoutQueue[0],
        );
      }

      return Promise.resolve({ resources: {} });
    });
  }

  const layout = {
    "@id": layoutPath,
    grid: { rows: 1, columns: 1 },
    regions: [{ "@id": `/v2/layouts/regions/${REGION}`, gridArea: ["a"] }],
  };

  it("keeps the previously loaded layout when a later request for it fails", async () => {
    // Screen builds its regions from layoutData.regions, so a null unmounts
    // every one of them: the scheduling state is dropped and the next good pull
    // restarts playback from the first slide. Content one pull out of date beats
    // a black screen, the same trade getRegions makes.
    wireApi();
    wireLayoutQueue({
      screens: [
        buildScreen(checksums),
        buildScreen({ ...checksums, layout: "layout-2" }),
      ],
      layouts: [layout, null],
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetPath, layoutPath)).toBe(2);
    expect(strategy.lastestScreenData.layoutData).toEqual(layout);
  });

  it("does not fall back to the synthetic layout of a campaign pull", async () => {
    // Campaign mode swaps in a full-screen layout with one hardcoded region.
    // Reusing it once the campaign is over would leave the client rendering a
    // region no playlist is scheduled for.
    wireApi();
    wireLayoutQueue({
      screens: [
        buildScreen(checksums),
        // Ending the campaign moves its checksum too, otherwise the second pull
        // serves campaignsData from cache and never leaves campaign mode.
        buildScreen({
          ...checksums,
          layout: "layout-2",
          campaigns: "campaigns-2",
        }),
      ],
      // Campaign mode builds its layout rather than fetching one, so the only
      // layout request in this test is the failing one on the second pull.
      layouts: [null],
    });

    const campaigns = [
      collection(campaignsPath, [
        {
          campaign: {
            "@id": `/v2/playlists/${PLAYLIST}`,
            published: { from: null, to: null },
            slides: slidesPath,
          },
        },
      ]),
      collection(campaignsPath, []),
    ];

    const previousGetAll = mockGetAllResultsFromPath.getMockImplementation();

    mockGetAllResultsFromPath.mockImplementation((path) => {
      if (path === campaignsPath) {
        return Promise.resolve(
          campaigns.length > 1 ? campaigns.shift() : campaigns[0],
        );
      }

      return previousGetAll(path);
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    expect(strategy.lastestScreenData.hasActiveCampaign).toBe(true);

    await strategy.getScreen(screenPath);

    expect(strategy.lastestScreenData.hasActiveCampaign).toBe(false);
    expect(strategy.lastestScreenData.layoutData).toBeNull();
  });

  it("does not fall back to the layout the screen has been moved away from", async () => {
    const otherLayoutPath = "/v2/layouts/01HRZ3NDEKTSV4RRFFQ69G5FAV";

    wireApi();
    wireLayoutQueue({
      screens: [
        buildScreen(checksums),
        {
          ...buildScreen({ ...checksums, layout: "layout-2" }),
          layout: otherLayoutPath,
        },
      ],
      layouts: [layout, null],
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    // The old layout's regions do not match the regionData this pull fetched.
    expect(strategy.lastestScreenData.layoutData).toBeNull();
  });

  it("keeps media cached when nothing failed", async () => {
    wireApi({ slide: buildSlide([mediaPath]) });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: screenPath });

    await strategy.getScreen(screenPath);
    await strategy.getScreen(screenPath);

    expect(callsFor(mockGetPath, mediaPath)).toBe(1);
  });
});
