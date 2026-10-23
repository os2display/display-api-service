import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/util/client-config-loader.js", () => ({
  default: {
    loadConfig: () => Promise.resolve({ schedulingInterval: 60000 }),
  },
}));

import ScheduleService from "../../client/service/schedule-service";

const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

/**
 * Build a minimal slide.
 *
 * @param {string} id Slide id.
 * @param {object} extra Extra slide properties.
 * @returns {object} The slide.
 */
function buildSlide(id, extra = {}) {
  return {
    "@id": `/v2/slides/${id}`,
    title: id,
    ...extra,
  };
}

/**
 * Build a region: one playlist holding the given slides.
 *
 * @param {Array} slidesData The slides.
 * @param {object} extra Extra playlist properties.
 * @returns {Array} The region.
 */
function buildRegion(slidesData, extra = {}) {
  return [
    {
      "@id": "/v2/playlists/01JB1D9E3ZMTFBT7CYHFHGX5KB",
      title: "Playlist",
      schedules: [],
      slidesData,
      ...extra,
    },
  ];
}

/**
 * Record every regionContent event for a region.
 *
 * @param {string} regionId The region id.
 * @returns {object} The recorded sends and a teardown.
 */
function recordSends(regionId) {
  const sends = [];
  const handler = (event) => sends.push(event.detail.slides);

  document.addEventListener(`regionContent-${regionId}`, handler);

  return {
    sends,
    stop: () =>
      document.removeEventListener(`regionContent-${regionId}`, handler),
  };
}

/**
 * Record the contentEmpty/contentNotEmpty events, in order.
 *
 * @returns {object} The recorded events and a teardown.
 */
function recordEmptyState() {
  const events = [];
  const onEmpty = () => events.push("contentEmpty");
  const onNotEmpty = () => events.push("contentNotEmpty");

  document.addEventListener("contentEmpty", onEmpty);
  document.addEventListener("contentNotEmpty", onNotEmpty);

  return {
    events,
    stop: () => {
      document.removeEventListener("contentEmpty", onEmpty);
      document.removeEventListener("contentNotEmpty", onNotEmpty);
    },
  };
}

describe("ScheduleService", () => {
  let service;
  let recorder;

  beforeEach(() => {
    vi.useFakeTimers();
    service = new ScheduleService();
    recorder = recordSends(REGION_ID);
  });

  afterEach(() => {
    recorder.stop();
    vi.useRealTimers();
  });

  it("sends slides the first time a region is updated", () => {
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));

    expect(recorder.sends).toHaveLength(1);
    expect(recorder.sends[0].map((slide) => slide.title)).toEqual(["a"]);
  });

  it("does not re-send identical content", () => {
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));

    expect(recorder.sends).toHaveLength(1);
  });

  it("re-sends when only feedData changed", () => {
    service.updateRegion(
      REGION_ID,
      buildRegion([buildSlide("a", { feedData: { entries: ["one"] } })]),
    );
    service.updateRegion(
      REGION_ID,
      buildRegion([buildSlide("a", { feedData: { entries: ["two"] } })]),
    );

    // Feeds are refetched on every pull and carry no checksum, so this hash is
    // the only thing that can notice they changed.
    expect(recorder.sends).toHaveLength(2);
    expect(recorder.sends[1][0].feedData).toEqual({ entries: ["two"] });
  });

  it("does not re-send when only a playlist field that is not rendered changed", () => {
    service.updateRegion(
      REGION_ID,
      buildRegion([buildSlide("a")], { title: "Before" }),
    );
    service.updateRegion(
      REGION_ID,
      buildRegion([buildSlide("a")], { title: "After" }),
    );

    // The region is no longer part of the hash input - only the slides it
    // produces are.
    expect(recorder.sends).toHaveLength(1);
  });

  it("replays the cached slides on regionReady, despite an unchanged hash", () => {
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));
    expect(recorder.sends).toHaveLength(1);

    service.regionReady(REGION_ID);

    expect(recorder.sends).toHaveLength(2);
    expect(recorder.sends[1].map((slide) => slide.title)).toEqual(["a"]);
  });

  it("delivers to a region that mounts after its content arrived", () => {
    // The push happens before React has mounted the region, so that dispatch is
    // lost. Without the replay the region would stay blank: updateRegion's hash
    // gate reports no change on every later pull.
    recorder.stop();
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));

    recorder = recordSends(REGION_ID);
    service.regionReady(REGION_ID);

    expect(recorder.sends).toHaveLength(1);
    expect(recorder.sends[0].map((slide) => slide.title)).toEqual(["a"]);
  });

  it("sends nothing on regionReady when no content is cached", () => {
    service.regionReady(REGION_ID);

    expect(recorder.sends).toHaveLength(0);
  });

  it("keeps the cached slides in step with checkScheduling", () => {
    const region = buildRegion([buildSlide("a")]);
    service.updateRegion(REGION_ID, region);

    // Same object ScheduleService holds, so checkScheduling recomputes from it.
    region[0].slidesData.push(buildSlide("b"));
    service.checkScheduling(REGION_ID);

    expect(recorder.sends).toHaveLength(2);

    service.regionReady(REGION_ID);

    // The replay has to hand out what checkScheduling last worked out, not the
    // set from before the schedule moved.
    expect(recorder.sends[2].map((slide) => slide.title)).toEqual(["a", "b"]);
  });

  it("reports empty content when every slide in the region is invalid", () => {
    // Region drops invalid slides, so holding them is not the same as having
    // something to show. Counting them kept contentEmpty at false, which
    // suppressed the fallback image and left the screen black.
    const state = recordEmptyState();

    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));
    expect(state.events).toEqual(["contentNotEmpty"]);

    service.updateRegion(
      REGION_ID,
      buildRegion([buildSlide("a", { invalid: true })]),
    );

    expect(state.events).toEqual(["contentNotEmpty", "contentEmpty"]);

    state.stop();
  });

  it("reports empty content when the last region with content is removed", () => {
    // A failed layout request renders no regions at all, so every region
    // unmounts. Without re-checking here the fallback image never comes back.
    const state = recordEmptyState();

    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));
    expect(state.events).toEqual(["contentNotEmpty"]);

    service.regionRemoved(REGION_ID);

    expect(state.events).toEqual(["contentNotEmpty", "contentEmpty"]);

    state.stop();
  });

  it("drops the cached region and its interval on regionRemoved", async () => {
    service.updateRegion(REGION_ID, buildRegion([buildSlide("a")]));

    // The interval is registered behind an await on the config.
    await vi.waitFor(() => expect(service.intervals[REGION_ID]).toBeDefined());

    service.regionRemoved(REGION_ID);

    expect(service.intervals[REGION_ID]).toBeUndefined();
    expect(service.regions[REGION_ID]).toBeUndefined();

    service.regionReady(REGION_ID);
    expect(recorder.sends).toHaveLength(1);
  });
});
