import { describe, it, expect, vi, beforeEach } from "vitest";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/core/client-config-loader.js", () => ({
  default: {
    loadConfig: vi.fn().mockResolvedValue({ pullStrategyInterval: 30000 }),
  },
}));

vi.mock("../../client/service/data-sync", () => {
  const MockDataSync = vi.fn(function () {
    this.start = vi.fn();
    this.stop = vi.fn();
  });
  return { default: MockDataSync };
});

vi.mock("../../client/service/schedule-service", () => {
  const MockScheduleService = vi.fn(function () {
    this.updateRegion = vi.fn();
    this.regionRemoved = vi.fn();
  });
  return { default: MockScheduleService };
});

const mockQuery = vi.fn();
vi.mock("../../client/core/api-query.js", () => ({
  query: (...args) => mockQuery(...args),
}));

vi.mock("../../client/util/id-from-path", () => ({
  default: (path) => {
    if (!path) return null;
    const match = path.match(/[A-Za-z0-9]{26}/);
    return match ? match[0] : null;
  },
}));

vi.mock("../../client/util/preview", () => ({
  screenForPlaylistPreview: vi.fn((playlist) => ({
    "@id": "/v2/screens/PREVIEW",
    regionData: { REGION01: [playlist] },
    layoutData: { grid: { rows: 1, columns: 1 }, regions: [] },
  })),
  screenForSlidePreview: vi.fn((slide) => ({
    "@id": "/v2/screens/PREVIEW",
    regionData: { REGION01: [{ slidesData: [slide] }] },
    layoutData: { grid: { rows: 1, columns: 1 }, regions: [] },
  })),
}));

import ContentService from "../../client/service/content-service";
import DataSync from "../../client/service/data-sync";
import ScheduleService from "../../client/service/schedule-service";
import logger from "../../client/core/logger.js";

function makeCallbacks() {
  return {
    current: {
      setScreen: vi.fn(),
      setIsContentEmpty: vi.fn(),
      updateRegionSlides: vi.fn(),
      onRegionReady: vi.fn(),
      onRegionRemoved: vi.fn(),
    },
  };
}

describe("ContentService", () => {
  let service;
  let callbacks;

  beforeEach(() => {
    vi.clearAllMocks();
    callbacks = makeCallbacks();
    service = new ContentService(callbacks);
  });

  describe("constructor", () => {
    it("creates a ScheduleService with the callbacks", () => {
      expect(ScheduleService).toHaveBeenCalledWith(callbacks);
      expect(service.scheduleService).toBeDefined();
    });
  });

  describe("start / stop", () => {
    it("wires onRegionReady and onRegionRemoved callbacks", () => {
      service.start();

      expect(callbacks.current.onRegionReady).toBe(service.regionReady);
      expect(callbacks.current.onRegionRemoved).toBe(service.regionRemoved);
    });

    it("does not start twice", () => {
      service.start();
      service.start();

      expect(logger.warn).toHaveBeenCalledWith(
        "Content service already started."
      );
    });

    it("clears callbacks on stop", () => {
      service.start();
      service.stop();

      expect(callbacks.current.onRegionReady).not.toBe(service.regionReady);
      expect(callbacks.current.onRegionRemoved).not.toBe(service.regionRemoved);
    });

    it("stop is a no-op if not started", () => {
      service.stop();

      // Should not throw or log.
      expect(logger.info).not.toHaveBeenCalledWith(
        "Content service stopped."
      );
    });
  });

  describe("startSyncing / stopSync", () => {
    it("creates a DataSync and starts it after config loads", async () => {
      service.startSyncing("/v2/screens/ABC");
      await vi.waitFor(() => expect(DataSync).toHaveBeenCalled());

      const config = DataSync.mock.calls[0][0];
      expect(config.entryPoint).toBe("/v2/screens/ABC");
      expect(config.interval).toBe(30000);

      const instance = DataSync.mock.results[0].value;
      expect(instance.start).toHaveBeenCalled();
    });

    it("does not create DataSync if stopSync is called before config resolves", async () => {
      service.startSyncing("/v2/screens/ABC");
      service.stopSync();

      // Let the config promise resolve.
      await new Promise((r) => setTimeout(r, 0));

      expect(DataSync).not.toHaveBeenCalled();
    });

    it("stops and nulls dataSync on stopSync", async () => {
      service.startSyncing("/v2/screens/ABC");
      await vi.waitFor(() => expect(DataSync).toHaveBeenCalled());

      const instance = DataSync.mock.results[0].value;
      service.stopSync();

      expect(instance.stop).toHaveBeenCalled();
      expect(service.dataSync).toBeNull();
    });
  });

  describe("contentHandler", () => {
    const makeScreen = (overrides = {}) => ({
      "@id": "/v2/screens/SCREEN01234567890123456789",
      title: "Test Screen",
      regionData: {
        region1: [{ "@id": "/v2/playlists/P1" }],
      },
      ...overrides,
    });

    it("calls setScreen when screen data changes", () => {
      service.contentHandler(makeScreen());

      expect(callbacks.current.setScreen).toHaveBeenCalledTimes(1);
      const screenArg = callbacks.current.setScreen.mock.calls[0][0];
      expect(screenArg["@id"]).toBe(
        "/v2/screens/SCREEN01234567890123456789"
      );
      // regionData should be stripped from the screen passed to setScreen.
      expect(screenArg.regionData).toBeUndefined();
    });

    it("does not call setScreen when screen data has not changed", () => {
      const screen = makeScreen();
      service.contentHandler(screen);
      service.contentHandler(screen);

      expect(callbacks.current.setScreen).toHaveBeenCalledTimes(1);
    });

    it("calls setScreen again when screen data changes", () => {
      service.contentHandler(makeScreen());
      service.contentHandler(makeScreen({ title: "Changed" }));

      expect(callbacks.current.setScreen).toHaveBeenCalledTimes(2);
    });

    it("always pushes region data to schedule service", () => {
      const screen = makeScreen();
      service.contentHandler(screen);

      expect(service.scheduleService.updateRegion).toHaveBeenCalledWith(
        "region1",
        screen.regionData.region1
      );
    });

    it("pushes region data even when screen hash has not changed", () => {
      const screen = makeScreen();
      service.contentHandler(screen);
      service.contentHandler(screen);

      expect(service.scheduleService.updateRegion).toHaveBeenCalledTimes(2);
    });

    it("pushes data for all regions", () => {
      const screen = makeScreen({
        regionData: {
          region1: [{ "@id": "/v2/playlists/P1" }],
          region2: [{ "@id": "/v2/playlists/P2" }],
        },
      });
      service.contentHandler(screen);

      expect(service.scheduleService.updateRegion).toHaveBeenCalledTimes(2);
    });
  });

  describe("regionReady", () => {
    it("sends current region data to schedule service", () => {
      service.currentScreen = {
        regionData: {
          region1: [{ "@id": "/v2/playlists/P1" }],
        },
      };

      service.regionReady("region1");

      expect(service.scheduleService.updateRegion).toHaveBeenCalledWith(
        "region1",
        service.currentScreen.regionData.region1
      );
    });

    it("does nothing when no current screen exists", () => {
      service.regionReady("region1");

      expect(service.scheduleService.updateRegion).not.toHaveBeenCalled();
    });
  });

  describe("regionRemoved", () => {
    it("delegates to schedule service", () => {
      service.regionRemoved("region1");

      expect(service.scheduleService.regionRemoved).toHaveBeenCalledWith(
        "region1"
      );
    });
  });

  describe("startPreview", () => {
    it("starts syncing for screen mode", async () => {
      const spy = vi.spyOn(service, "startSyncing");

      await service.startPreview("screen", "SCREEN01234567890123456789");

      expect(spy).toHaveBeenCalledWith(
        "/v2/screens/SCREEN01234567890123456789"
      );
    });

    it("fetches playlist and slides for playlist mode", async () => {
      const playlist = {
        "@id": "/v2/playlists/PLSTAAA0000000000000000001",
        slides: "/v2/playlists/PLSTAAA0000000000000000001/slides",
      };
      const slidesResponse = {
        "hydra:member": [
          {
            slide: {
              "@id": "/v2/slides/SLIDEAAA000000000000000001",
              templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
              media: [],
            },
          },
        ],
      };
      const templateData = { "@id": "/v2/templates/TMPLAAA0000000000000000001" };

      mockQuery
        .mockResolvedValueOnce(playlist)
        .mockResolvedValueOnce(slidesResponse)
        .mockResolvedValueOnce(templateData);

      await service.startPreview("playlist", "PLSTAAA0000000000000000001");

      expect(mockQuery).toHaveBeenCalledWith(
        "getV2PlaylistsById",
        { id: "PLSTAAA0000000000000000001" },
        true
      );
      expect(callbacks.current.setScreen).toHaveBeenCalled();
    });

    it("fetches slide and attaches references for slide mode", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: ["/v2/media/MDIAAA00000000000000000001"],
      };
      const templateData = { "@id": "/v2/templates/TMPLAAA0000000000000000001" };
      const mediaData = { "@id": "/v2/media/MDIAAA00000000000000000001" };

      mockQuery
        .mockResolvedValueOnce(slide)
        .mockResolvedValueOnce(templateData)
        .mockResolvedValueOnce(mediaData);

      await service.startPreview("slide", "SLIDEAAA000000000000000001");

      expect(mockQuery).toHaveBeenCalledWith(
        "getV2SlidesById",
        { id: "SLIDEAAA000000000000000001" },
        true
      );
      expect(callbacks.current.setScreen).toHaveBeenCalled();
    });

    it("logs error for unsupported mode", async () => {
      await service.startPreview("unknown", "123");

      expect(logger.error).toHaveBeenCalledWith(
        "Unsupported preview mode: unknown."
      );
    });

    it("catches and logs errors", async () => {
      mockQuery.mockRejectedValueOnce(new Error("Network error"));

      await service.startPreview("slide", "SLIDEAAA000000000000000001");

      expect(logger.error).toHaveBeenCalledWith(
        expect.stringContaining("Preview failed")
      );
    });
  });

  describe("attachReferencesToSlide", () => {
    it("fetches template, media, and feed data", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: ["/v2/media/MDIAAA00000000000000000001"],
        feed: { feedUrl: "/v2/feeds/FEEDAAA0000000000000000001" },
        theme: "/v2/themes/THMEAAA0000000000000000001",
      };

      const templateData = { "@id": "/v2/templates/TMPLAAA0000000000000000001" };
      const mediaData = { "@id": "/v2/media/MDIAAA00000000000000000001" };
      const feedData = [{ title: "Feed item" }];
      const themeData = { "@id": "/v2/themes/THMEAAA0000000000000000001" };

      // Order: template, feed, then each media (loop), then theme.
      mockQuery
        .mockResolvedValueOnce(templateData)
        .mockResolvedValueOnce(feedData)
        .mockResolvedValueOnce(mediaData)
        .mockResolvedValueOnce(themeData);

      await ContentService.attachReferencesToSlide(slide);

      expect(slide.templateData).toEqual(templateData);
      expect(slide.mediaData["/v2/media/MDIAAA00000000000000000001"]).toEqual(
        mediaData
      );
      expect(slide.feedData).toEqual(feedData);
      expect(slide.theme).toEqual(themeData);
    });

    it("marks slide invalid when template fetch fails", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: [],
      };

      mockQuery.mockRejectedValueOnce(new Error("Not found"));

      await ContentService.attachReferencesToSlide(slide);

      expect(slide.invalid).toBe(true);
      expect(slide.templateData).toBeNull();
      expect(slide.mediaData).toEqual({});
      expect(slide.feedData).toBeNull();
    });

    it("sets feedData to empty array when no feed configured", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: [],
      };

      mockQuery.mockResolvedValueOnce({ "@id": "/v2/templates/T" });

      await ContentService.attachReferencesToSlide(slide);

      expect(slide.feedData).toEqual([]);
    });

    it("sets media to null on fetch failure", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: ["/v2/media/MDIAAA00000000000000000001"],
      };

      mockQuery
        .mockResolvedValueOnce({ "@id": "/v2/templates/T" })
        .mockRejectedValueOnce(new Error("Media error"));

      await ContentService.attachReferencesToSlide(slide);

      expect(slide.mediaData["/v2/media/MDIAAA00000000000000000001"]).toBeNull();
    });

    it("keeps theme as string when theme fetch fails", async () => {
      const slide = {
        "@id": "/v2/slides/SLIDEAAA000000000000000001",
        templateInfo: { "@id": "/v2/templates/TMPLAAA0000000000000000001" },
        media: [],
        theme: "/v2/themes/THMEAAA0000000000000000001",
      };

      mockQuery
        .mockResolvedValueOnce({ "@id": "/v2/templates/T" })
        .mockRejectedValueOnce(new Error("Theme error"));

      await ContentService.attachReferencesToSlide(slide);

      expect(slide.theme).toBe("/v2/themes/THMEAAA0000000000000000001");
    });
  });
});
