import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/util/client-config-loader.js", () => ({
  default: {
    loadConfig: vi.fn().mockResolvedValue({ schedulingInterval: 60000 }),
  },
}));

import ScheduleService from "../../client/service/schedule-service";

function makeCallbacks() {
  return {
    current: {
      setIsContentEmpty: vi.fn(),
      updateRegionSlides: vi.fn(),
    },
  };
}

describe("ScheduleService", () => {
  describe("findScheduledSlides (static)", () => {
    beforeEach(() => {
      vi.useFakeTimers();
      vi.setSystemTime(new Date("2025-06-15T12:00:00Z"));
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    const makeSlide = (id, published = {}) => ({
      "@id": `/v2/slides/${id}`,
      "@type": "Slide",
      title: `Slide ${id}`,
      published,
    });

    const makePlaylist = (id, slides, options = {}) => ({
      "@id": `/v2/playlists/${id}`,
      "@type": "Playlist",
      title: `Playlist ${id}`,
      schedules: [],
      published: {},
      slidesData: slides,
      ...options,
    });

    it("returns all slides from a published playlist with no schedules", () => {
      const slides = [makeSlide("A"), makeSlide("B")];
      const playlists = [makePlaylist("P1", slides)];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(2);
      expect(result[0].title).toBe("Slide A");
      expect(result[1].title).toBe("Slide B");
    });

    it("excludes slides from unpublished playlists", () => {
      const slides = [makeSlide("A")];
      const playlists = [
        makePlaylist("P1", slides, {
          published: { from: "2025-06-16T00:00:00Z" },
        }),
      ];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(0);
    });

    it("includes slides when playlist schedule is active", () => {
      const slides = [makeSlide("A")];
      const playlists = [
        makePlaylist("P1", slides, {
          schedules: [
            {
              rrule: "DTSTART:20250601T120000Z\nRRULE:FREQ=DAILY",
              duration: 3600,
            },
          ],
        }),
      ];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(1);
    });

    it("excludes slides when playlist schedule is inactive", () => {
      const slides = [makeSlide("A")];
      const playlists = [
        makePlaylist("P1", slides, {
          schedules: [
            {
              rrule: "DTSTART:20250601T080000Z\nRRULE:FREQ=DAILY",
              duration: 3600,
            },
          ],
        }),
      ];

      // Schedule is 08:00-09:00, current time is 12:00
      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(0);
    });

    it("filters unpublished slides within a published playlist", () => {
      const slides = [
        makeSlide("A"), // published (no dates = always published)
        makeSlide("B", { from: "2025-06-16T00:00:00Z" }), // not yet published
      ];
      const playlists = [makePlaylist("P1", slides)];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(1);
      expect(result[0].title).toBe("Slide A");
    });

    it("sets executionId on each slide in EXE-ID-xxx format", () => {
      const slides = [makeSlide("A")];
      const playlists = [makePlaylist("P1", slides)];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result[0].executionId).toMatch(/^EXE-ID-.+/);
    });

    it("produces deterministic executionIds for the same inputs", () => {
      const slides = [makeSlide("A")];
      const playlists = [makePlaylist("P1", slides)];

      const result1 = ScheduleService.findScheduledSlides(playlists, "region1");
      const result2 = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result1[0].executionId).toBe(result2[0].executionId);
    });

    it("produces different executionIds for different regions", () => {
      const slides = [makeSlide("A")];
      const playlists = [makePlaylist("P1", slides)];

      const result1 = ScheduleService.findScheduledSlides(
        playlists,
        "region1"
      );
      const result2 = ScheduleService.findScheduledSlides(
        playlists,
        "region2"
      );
      expect(result1[0].executionId).not.toBe(result2[0].executionId);
    });

    it("deep clones slides so modifications do not affect input", () => {
      const originalSlide = makeSlide("A");
      const playlists = [makePlaylist("P1", [originalSlide])];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      result[0].title = "Modified";

      expect(originalSlide.title).toBe("Slide A");
    });

    it("handles multiple playlists with mixed published states", () => {
      const playlists = [
        makePlaylist("P1", [makeSlide("A")]), // published
        makePlaylist("P2", [makeSlide("B")], {
          published: { to: "2025-06-14T00:00:00Z" },
        }), // expired
        makePlaylist("P3", [makeSlide("C")]), // published
      ];

      const result = ScheduleService.findScheduledSlides(playlists, "region1");
      expect(result).toHaveLength(2);
      expect(result[0].title).toBe("Slide A");
      expect(result[1].title).toBe("Slide C");
    });
  });

  describe("instance methods", () => {
    let service;
    let callbacks;

    beforeEach(() => {
      callbacks = makeCallbacks();
      service = new ScheduleService(callbacks);
      vi.useFakeTimers();
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it("sendSlides calls updateRegionSlides callback", () => {
      const slides = [{ "@id": "/v2/slides/A" }];
      service.sendSlides("region1", slides);

      expect(callbacks.current.updateRegionSlides).toHaveBeenCalledWith("region1", slides);
    });

    it("checkForEmptyContent calls setIsContentEmpty(true) when no regions have slides", () => {
      service.regions = { r1: { slides: [] } };
      service.contentEmpty = false; // force change detection
      service.checkForEmptyContent();

      expect(callbacks.current.setIsContentEmpty).toHaveBeenCalledWith(true);
    });

    it("checkForEmptyContent calls setIsContentEmpty(false) when regions have slides", () => {
      service.regions = { r1: { slides: [{ "@id": "s1" }] } };
      service.contentEmpty = true; // force change detection
      service.checkForEmptyContent();

      expect(callbacks.current.setIsContentEmpty).toHaveBeenCalledWith(false);
    });

    it("regionRemoved clears interval and cached data", () => {
      const intervalId = setInterval(() => {}, 1000);
      service.intervals.region1 = intervalId;
      service.regions.region1 = { hash: "abc", slides: [] };

      service.regionRemoved("region1");

      expect(service.intervals.region1).toBeUndefined();
      expect(service.regions.region1).toBeUndefined();
    });

    it("updateRegion is a no-op when regionId is falsy", () => {
      service.updateRegion(null, []);

      expect(callbacks.current.updateRegionSlides).not.toHaveBeenCalled();
    });

    it("updateRegion is a no-op when region is falsy", () => {
      service.updateRegion("region1", null);
      expect(service.regions.region1).toBeUndefined();
    });
  });
});
