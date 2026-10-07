import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/util/client-config-loader.js", () => ({
  default: {
    loadConfig: () => Promise.resolve({ schedulingInterval: 60000 }),
  },
}));

// Neither is exercised here, but both reach for the network at import time.
vi.mock("../../client/data-sync/pull-strategy", () => ({
  default: class PullStrategy {},
}));

vi.mock("../../client/data-sync/data-sync", () => ({
  default: class DataSync {},
}));

import ContentService from "../../client/service/content-service";

const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

/**
 * Build a screen as PullStrategy delivers it.
 *
 * @param {object} options Options.
 * @param {Array} options.slideTitles Slides in the region's single playlist.
 * @param {object} options.layoutData Layout, passed by reference to mimic a
 *   pull that served the layout from cache.
 * @param {string} options.regionsChecksum The screen's regions checksum.
 * @returns {object} The screen.
 */
function buildScreen({ slideTitles, layoutData, regionsChecksum }) {
  return {
    "@id": "/v2/screens/01JB1D9E3ZMTFBT7CYHFHGX5KC",
    title: "Screen",
    relationsChecksum: { layout: "unchanged", regions: regionsChecksum },
    layoutData,
    regionData: {
      [REGION_ID]: [
        {
          "@id": "/v2/playlists/01JB1D9E3ZMTFBT7CYHFHGX5KB",
          title: "Playlist",
          schedules: [],
          slidesData: slideTitles.map((title) => ({
            "@id": `/v2/slides/${title}`,
            title,
          })),
        },
      ],
    },
  };
}

/**
 * Dispatch a content event.
 *
 * @param {object} screen The screen.
 */
function dispatchContent(screen) {
  document.dispatchEvent(new CustomEvent("content", { detail: { screen } }));
}

describe("ContentService", () => {
  let contentService;
  let screens;
  let regionSends;
  let screenHandler;
  let regionHandler;

  beforeEach(() => {
    vi.useFakeTimers();

    screens = [];
    regionSends = [];

    screenHandler = (event) => screens.push(event.detail.screen);
    regionHandler = (event) => regionSends.push(event.detail.slides);

    document.addEventListener("screen", screenHandler);
    document.addEventListener(`regionContent-${REGION_ID}`, regionHandler);

    contentService = new ContentService();
    contentService.start();
  });

  afterEach(() => {
    contentService.stop();
    document.removeEventListener("screen", screenHandler);
    document.removeEventListener(`regionContent-${REGION_ID}`, regionHandler);
    vi.useRealTimers();
  });

  it("delivers new region content when the layout came from cache", () => {
    // A pull that reuses the cached layout hands back the very same layoutData
    // object, so the region components see an unchanged prop and never ask for
    // content. Delivery has to come from here instead (#507).
    const layoutData = {
      grid: { rows: 1, columns: 1 },
      regions: [{ "@id": `/v2/layouts/regions/${REGION_ID}`, gridArea: ["a"] }],
    };

    dispatchContent(
      buildScreen({
        slideTitles: ["a"],
        layoutData,
        regionsChecksum: "before",
      }),
    );
    dispatchContent(
      buildScreen({
        slideTitles: ["a", "b"],
        layoutData,
        regionsChecksum: "after",
      }),
    );

    expect(regionSends).toHaveLength(2);
    expect(regionSends[1].map((slide) => slide.title)).toEqual(["a", "b"]);
  });

  it("emits the screen on every content event", () => {
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };
    const screen = buildScreen({
      slideTitles: ["a"],
      layoutData,
      regionsChecksum: "same",
    });

    dispatchContent(screen);
    dispatchContent(screen);

    expect(screens).toHaveLength(2);
  });

  it("emits the screen without regionData and leaves relationsChecksum alone", () => {
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };

    dispatchContent(
      buildScreen({
        slideTitles: ["a"],
        layoutData,
        regionsChecksum: "abc",
      }),
    );

    expect(screens[0]).not.toHaveProperty("regionData");
    expect(screens[0].relationsChecksum).toEqual({
      layout: "unchanged",
      regions: "abc",
    });
  });

  it("hands a region its content when it reports ready after the push", () => {
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };

    document.removeEventListener(`regionContent-${REGION_ID}`, regionHandler);
    dispatchContent(
      buildScreen({
        slideTitles: ["a"],
        layoutData,
        regionsChecksum: "abc",
      }),
    );
    document.addEventListener(`regionContent-${REGION_ID}`, regionHandler);

    document.dispatchEvent(
      new CustomEvent("regionReady", { detail: { id: REGION_ID } }),
    );

    expect(regionSends).toHaveLength(1);
    expect(regionSends[0].map((slide) => slide.title)).toEqual(["a"]);
  });

  it("restores content for a region that unmounted and remounted", async () => {
    // Changing a region's type swaps the component behind an unchanged region
    // id, so Screen unmounts one and mounts the other in a single commit and
    // React runs the outgoing cleanup before the incoming effects. regionReady
    // therefore arrives with the cache regionRemoved has just dropped, and the
    // region showed nothing until a later pull happened to change its content.
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };

    dispatchContent(
      buildScreen({
        slideTitles: ["a"],
        layoutData,
        regionsChecksum: "abc",
      }),
    );

    expect(regionSends).toHaveLength(1);

    document.dispatchEvent(
      new CustomEvent("regionRemoved", { detail: { id: REGION_ID } }),
    );
    document.dispatchEvent(
      new CustomEvent("regionReady", { detail: { id: REGION_ID } }),
    );

    expect(regionSends).toHaveLength(2);
    expect(regionSends[1].map((slide) => slide.title)).toEqual(["a"]);

    // regionRemoved cleared the scheduling interval too, so it has to come back
    // with the content or the region never picks up a schedule change again.
    await vi.waitFor(() =>
      expect(contentService.scheduleService.intervals[REGION_ID]).toBeDefined(),
    );
  });

  it("sends nothing for a region the current screen does not have", () => {
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };

    dispatchContent(
      buildScreen({
        slideTitles: ["a"],
        layoutData,
        regionsChecksum: "abc",
      }),
    );

    document.dispatchEvent(
      new CustomEvent("regionRemoved", { detail: { id: "unknown-region" } }),
    );
    document.dispatchEvent(
      new CustomEvent("regionReady", { detail: { id: "unknown-region" } }),
    );

    expect(regionSends).toHaveLength(1);
  });

  it("survives a screen with no regionData", () => {
    const layoutData = { grid: { rows: 1, columns: 1 }, regions: [] };
    const screen = buildScreen({
      slideTitles: [],
      layoutData,
      regionsChecksum: "abc",
    });
    delete screen.regionData;

    expect(() => dispatchContent(screen)).not.toThrow();
    expect(screens).toHaveLength(1);
  });
});
