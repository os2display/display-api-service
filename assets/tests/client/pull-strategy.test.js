import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

// --- Hoisted mocks ---
const { mockDispatch, endpoints } = vi.hoisted(() => {
  const mockDispatch = vi.fn();
  const endpoints = {};
  [
    "getV2ScreensById",
    "getV2ScreensByIdScreenGroups",
    "getV2ScreenGroupsByIdCampaigns",
    "getV2ScreensByIdCampaigns",
    "getV2LayoutsById",
    "getV2ScreensByIdRegionsAndRegionIdPlaylists",
    "getV2PlaylistsByIdSlides",
    "getV2TemplatesById",
    "getV2MediaById",
    "getV2FeedsByIdData",
  ].forEach((name) => {
    endpoints[name] = {
      initiate: (args, opts) => ({ _endpoint: name, _args: args, _opts: opts }),
      select: () => () => undefined,
    };
  });
  return { mockDispatch, endpoints };
});

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/core/client-config-loader.js", () => ({
  default: { loadConfig: vi.fn() },
}));

vi.mock("../../client/redux/store.js", () => ({
  clientStore: { dispatch: mockDispatch, getState: () => ({}) },
}));

vi.mock("../../client/redux/enhanced-api.ts", () => ({
  clientApi: {
    endpoints,
    reducerPath: "clientApi",
    reducer: (state = {}) => state,
    middleware: () => (next) => (action) => next(action),
  },
}));

import PullStrategy from "../../client/service/pull-strategy";
import logger from "../../client/core/logger.js";
import ClientConfigLoader from "../../client/core/client-config-loader.js";

// --- Test IDs (26 alphanumeric chars each, required by idFromPath) ---
const SCREEN_ID = "SCREEN0001AAAAAAAAAAAAAAAA";
const LAYOUT_ID = "LAYOUT0001AAAAAAAAAAAAAAAA";
const REGION_ID = "REGION0001AAAAAAAAAAAAAAAA";
const PLAYLIST_ID = "PLAYLS0001AAAAAAAAAAAAAAAA";
const SLIDE_ID = "SLIDES0001AAAAAAAAAAAAAAAA";
const TEMPLATE_ID = "TEMPLT0001AAAAAAAAAAAAAAAA";
const MEDIA_ID_1 = "MEDIAS0001AAAAAAAAAAAAAAAA";
const MEDIA_ID_2 = "MEDIAS0002AAAAAAAAAAAAAAAA";
const FEED_ID = "FEEDSS0001AAAAAAAAAAAAAAAA";
const CAMPAIGN_REGION_ID = "01G112XBWFPY029RYFB8X2H4KD";

const SCREEN_PATH = `/v2/screens/${SCREEN_ID}`;

// --- Factories ---
function makeScreen(overrides = {}) {
  return {
    "@id": SCREEN_PATH,
    layout: `/v2/layouts/${LAYOUT_ID}`,
    regions: [
      `/v2/screens/${SCREEN_ID}/regions/${REGION_ID}/playlists`,
    ],
    relationsChecksum: {
      campaigns: "aaa",
      inScreenGroups: "bbb",
      layout: "ccc",
      regions: "ddd",
    },
    ...overrides,
  };
}

function makeSlide(id = SLIDE_ID, overrides = {}) {
  return {
    "@id": `/v2/slides/${id}`,
    templateInfo: { "@id": `/v2/templates/${TEMPLATE_ID}` },
    media: [],
    relationsChecksum: { templateInfo: "t1", media: "m1" },
    ...overrides,
  };
}

function makePlaylist(id = PLAYLIST_ID) {
  return { "@id": `/v2/playlists/${id}` };
}

function makeLayout() {
  return {
    "@id": `/v2/layouts/${LAYOUT_ID}`,
    grid: { rows: 1, columns: 1 },
    regions: [
      { "@id": `/v2/layouts/regions/${REGION_ID}`, gridArea: ["a"] },
    ],
  };
}

function makeTemplateData() {
  return { "@id": `/v2/templates/${TEMPLATE_ID}`, resources: {} };
}

function hydra(members) {
  return { "hydra:member": members, "hydra:totalItems": members.length };
}

// --- Response helpers ---
function setupResponses(responseMap) {
  mockDispatch.mockImplementation((action) => ({
    unwrap: () => {
      const handler = responseMap[action._endpoint];
      if (handler === undefined) {
        return Promise.reject(
          new Error(`No mock response for ${action._endpoint}`),
        );
      }
      if (typeof handler === "function") {
        return handler(action._args, action._opts);
      }
      return Promise.resolve(handler);
    },
    unsubscribe: vi.fn(),
  }));
}

function setupBasicResponses(overrides = {}) {
  setupResponses({
    getV2ScreensById: makeScreen(),
    getV2ScreensByIdScreenGroups: { "hydra:member": [] },
    getV2ScreensByIdCampaigns: { "hydra:member": [] },
    getV2LayoutsById: makeLayout(),
    getV2ScreensByIdRegionsAndRegionIdPlaylists: hydra([
      { playlist: makePlaylist() },
    ]),
    getV2PlaylistsByIdSlides: hydra([{ slide: makeSlide() }]),
    getV2TemplatesById: makeTemplateData(),
    ...overrides,
  });
}

// --- Content callback capture ---
function captureContentCallback() {
  const captured = { screen: null, callCount: 0 };
  const callback = (screen) => {
    captured.screen = screen;
    captured.callCount += 1;
  };
  return {
    callback,
    get screen() {
      return captured.screen;
    },
    get callCount() {
      return captured.callCount;
    },
  };
}

// --- Dispatch call inspection ---
function getDispatchCallsFor(endpoint) {
  return mockDispatch.mock.calls
    .filter(([action]) => action._endpoint === endpoint)
    .map(([action]) => ({
      args: action._args,
      forceRefetch: action._opts?.forceRefetch,
    }));
}

// --- Tests ---
describe("PullStrategy.getScreen", () => {
  let strategy;
  let contentCapture;

  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2025-06-15T12:00:00Z"));
    vi.clearAllMocks();

    ClientConfigLoader.loadConfig.mockResolvedValue({
      relationsChecksumEnabled: false,
    });

    contentCapture = captureContentCallback();
    strategy = new PullStrategy({
      entryPoint: SCREEN_PATH,
      interval: 60000,
    }, contentCapture.callback);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe("early returns", () => {
    it("aborts when screen fetch throws", async () => {
      setupResponses({
        getV2ScreensById: () => Promise.reject(new Error("Network error")),
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(0);
      expect(logger.warn).toHaveBeenCalledWith(
        expect.stringContaining("not loaded. Aborting content update"),
      );
    });

    it("aborts when screen is null", async () => {
      setupResponses({
        getV2ScreensById: null,
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(0);
      expect(logger.warn).toHaveBeenCalledWith(
        expect.stringContaining("not loaded"),
      );
    });
  });

  describe("active campaign path", () => {
    it("builds synthetic layout when campaign is active", async () => {
      const activeCampaign = {
        "@id": `/v2/playlists/${PLAYLIST_ID}`,
        published: {},
      };

      setupResponses({
        getV2ScreensById: makeScreen(),
        getV2ScreensByIdScreenGroups: { "hydra:member": [] },
        getV2ScreensByIdCampaigns: {
          "hydra:member": [{ campaign: activeCampaign }],
        },
        getV2PlaylistsByIdSlides: hydra([{ slide: makeSlide() }]),
        getV2TemplatesById: makeTemplateData(),
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(1);
      const { screen } = contentCapture;
      expect(screen.hasActiveCampaign).toBe(true);
      expect(screen.layoutData.grid).toEqual({ rows: 1, columns: 1 });
      expect(screen.layoutData.regions[0]["@id"]).toContain(
        CAMPAIGN_REGION_ID,
      );
    });

    it("falls through to normal path when campaign is expired", async () => {
      const expiredCampaign = {
        "@id": `/v2/playlists/${PLAYLIST_ID}`,
        published: { to: "2020-01-01T00:00:00Z" },
      };

      setupBasicResponses({
        getV2ScreensByIdCampaigns: {
          "hydra:member": [{ campaign: expiredCampaign }],
        },
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(1);
      expect(contentCapture.screen.hasActiveCampaign).toBe(false);
      expect(contentCapture.screen.layoutData["@id"]).toContain(LAYOUT_ID);
    });
  });

  describe("normal path", () => {
    it("fetches layout, regions, and slides", async () => {
      setupBasicResponses();

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(1);
      const { screen } = contentCapture;
      expect(screen.hasActiveCampaign).toBe(false);
      expect(screen.layoutData["@id"]).toContain(LAYOUT_ID);
      expect(screen.regionData[REGION_ID]).toBeDefined();

      const slide = screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.templateData).toEqual(makeTemplateData());
      expect(slide.mediaData).toEqual({});
    });

    it("aborts when layout fetch throws", async () => {
      setupResponses({
        getV2ScreensById: makeScreen(),
        getV2ScreensByIdScreenGroups: { "hydra:member": [] },
        getV2ScreensByIdCampaigns: { "hydra:member": [] },
        getV2LayoutsById: () => Promise.reject(new Error("Layout error")),
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(0);
      expect(logger.warn).toHaveBeenCalledWith(
        expect.stringContaining("not loaded. Aborting content update"),
      );
    });

    it("aborts when layout is null", async () => {
      setupResponses({
        getV2ScreensById: makeScreen(),
        getV2ScreensByIdScreenGroups: { "hydra:member": [] },
        getV2ScreensByIdCampaigns: { "hydra:member": [] },
        getV2LayoutsById: null,
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(0);
      expect(logger.warn).toHaveBeenCalledWith(
        expect.stringContaining("not loaded. Aborting content update"),
      );
    });
  });

  describe("slide enrichment", () => {
    it("marks slide as invalid when templateInfo is missing", async () => {
      const slideNoTemplate = makeSlide(SLIDE_ID, { templateInfo: {} });

      setupBasicResponses({
        getV2PlaylistsByIdSlides: hydra([{ slide: slideNoTemplate }]),
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(1);
      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.invalid).toBe(true);
      expect(slide.templateData).toBeNull();
      expect(slide.mediaData).toEqual({});
    });

    it("marks slide as invalid when template fetch fails", async () => {
      setupBasicResponses({
        getV2TemplatesById: () =>
          Promise.reject(new Error("Template error")),
      });

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.callCount).toBe(1);
      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.invalid).toBe(true);
      expect(slide.templateData).toBeNull();
    });

    it("fetches media for each media path", async () => {
      const slideWithMedia = makeSlide(SLIDE_ID, {
        media: [`/v2/media/${MEDIA_ID_1}`, `/v2/media/${MEDIA_ID_2}`],
      });
      const media1 = { "@id": `/v2/media/${MEDIA_ID_1}`, title: "img1" };
      const media2 = { "@id": `/v2/media/${MEDIA_ID_2}`, title: "img2" };

      setupBasicResponses({
        getV2PlaylistsByIdSlides: hydra([{ slide: slideWithMedia }]),
        getV2MediaById: (args) => {
          if (args.id === MEDIA_ID_1) return Promise.resolve(media1);
          if (args.id === MEDIA_ID_2) return Promise.resolve(media2);
          return Promise.reject(new Error("Unknown media"));
        },
      });

      await strategy.getScreen(SCREEN_PATH);

      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.mediaData[`/v2/media/${MEDIA_ID_1}`]).toEqual(media1);
      expect(slide.mediaData[`/v2/media/${MEDIA_ID_2}`]).toEqual(media2);
    });

    it("sets null for failed media fetch", async () => {
      const slideWithMedia = makeSlide(SLIDE_ID, {
        media: [`/v2/media/${MEDIA_ID_1}`, `/v2/media/${MEDIA_ID_2}`],
      });
      const media1 = { "@id": `/v2/media/${MEDIA_ID_1}`, title: "img1" };

      setupBasicResponses({
        getV2PlaylistsByIdSlides: hydra([{ slide: slideWithMedia }]),
        getV2MediaById: (args) => {
          if (args.id === MEDIA_ID_1) return Promise.resolve(media1);
          return Promise.reject(new Error("Not found"));
        },
      });

      await strategy.getScreen(SCREEN_PATH);

      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.mediaData[`/v2/media/${MEDIA_ID_1}`]).toEqual(media1);
      expect(slide.mediaData[`/v2/media/${MEDIA_ID_2}`]).toBeNull();
    });

    it("fetches feed data when feedUrl is present", async () => {
      const slideWithFeed = makeSlide(SLIDE_ID, {
        feed: { feedUrl: `/v2/feeds/${FEED_ID}` },
      });
      const feedData = { data: [{ title: "Feed item" }] };

      setupBasicResponses({
        getV2PlaylistsByIdSlides: hydra([{ slide: slideWithFeed }]),
        getV2FeedsByIdData: feedData,
      });

      await strategy.getScreen(SCREEN_PATH);

      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.feedData).toEqual(feedData);
    });

    it("sets feedData to null when feed fetch fails", async () => {
      const slideWithFeed = makeSlide(SLIDE_ID, {
        feed: { feedUrl: `/v2/feeds/${FEED_ID}` },
      });

      setupBasicResponses({
        getV2FeedsByIdData: () => Promise.reject(new Error("Feed error")),
        getV2PlaylistsByIdSlides: hydra([{ slide: slideWithFeed }]),
      });

      await strategy.getScreen(SCREEN_PATH);

      const slide =
        contentCapture.screen.regionData[REGION_ID][0].slidesData[0];
      expect(slide.feedData).toBeNull();
    });
  });

  describe("checksum caching", () => {
    beforeEach(() => {
      ClientConfigLoader.loadConfig.mockResolvedValue({
        relationsChecksumEnabled: true,
      });
    });

    it("force-fetches everything on first call", async () => {
      setupBasicResponses();

      await strategy.getScreen(SCREEN_PATH);

      const layoutCalls = getDispatchCallsFor("getV2LayoutsById");
      expect(layoutCalls[0].forceRefetch).toBe(true);
    });

    it("skips refetch on second call with unchanged checksums", async () => {
      setupBasicResponses();
      await strategy.getScreen(SCREEN_PATH);

      mockDispatch.mockClear();
      setupBasicResponses();
      await strategy.getScreen(SCREEN_PATH);

      const layoutCalls = getDispatchCallsFor("getV2LayoutsById");
      expect(layoutCalls[0].forceRefetch).toBe(false);
    });

    it("refetches when checksum changes", async () => {
      setupBasicResponses();
      await strategy.getScreen(SCREEN_PATH);

      mockDispatch.mockClear();
      setupBasicResponses({
        getV2ScreensById: makeScreen({
          relationsChecksum: {
            campaigns: "aaa",
            inScreenGroups: "bbb",
            layout: "CHANGED",
            regions: "ddd",
          },
        }),
      });
      await strategy.getScreen(SCREEN_PATH);

      const layoutCalls = getDispatchCallsFor("getV2LayoutsById");
      expect(layoutCalls[0].forceRefetch).toBe(true);
    });
  });

  describe("campaign-to-normal transition", () => {
    it("force-fetches layout after campaign ends", async () => {
      ClientConfigLoader.loadConfig.mockResolvedValue({
        relationsChecksumEnabled: true,
      });

      // First call: active campaign
      const activeCampaign = {
        "@id": `/v2/playlists/${PLAYLIST_ID}`,
        published: {},
      };

      setupResponses({
        getV2ScreensById: makeScreen(),
        getV2ScreensByIdScreenGroups: { "hydra:member": [] },
        getV2ScreensByIdCampaigns: {
          "hydra:member": [{ campaign: activeCampaign }],
        },
        getV2PlaylistsByIdSlides: hydra([{ slide: makeSlide() }]),
        getV2TemplatesById: makeTemplateData(),
      });

      await strategy.getScreen(SCREEN_PATH);
      expect(contentCapture.screen.hasActiveCampaign).toBe(true);

      // Second call: no campaign, same checksums
      mockDispatch.mockClear();
      setupBasicResponses();

      await strategy.getScreen(SCREEN_PATH);

      expect(contentCapture.screen.hasActiveCampaign).toBe(false);
      const layoutCalls = getDispatchCallsFor("getV2LayoutsById");
      expect(layoutCalls[0].forceRefetch).toBe(true);
    });
  });
});
