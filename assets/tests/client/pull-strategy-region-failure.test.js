import { describe, it, expect, vi, beforeEach } from "vitest";

const { mockGetAllResultsFromPath, mockLogger } = vi.hoisted(() => ({
  mockGetAllResultsFromPath: vi.fn(),
  mockLogger: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

vi.mock("../../client/logger/logger", () => ({ default: mockLogger }));

vi.mock("../../client/data-sync/api-helper", () => ({
  default: vi.fn().mockImplementation(function () {
    this.getAllResultsFromPath = mockGetAllResultsFromPath;
    this.getPath = vi.fn();
  }),
}));

import PullStrategy from "../../client/data-sync/pull-strategy";

const SCREEN = "01ARZ3NDEKTSV4RRFFQ69G5FAV";
const REGION_A = "01BRZ3NDEKTSV4RRFFQ69G5FAV";
const REGION_B = "01CRZ3NDEKTSV4RRFFQ69G5FAV";
const REGION_C = "01DRZ3NDEKTSV4RRFFQ69G5FAV";

const pathA = `/v2/screens/${SCREEN}/regions/${REGION_A}/playlists`;
const pathB = `/v2/screens/${SCREEN}/regions/${REGION_B}/playlists`;
const pathC = `/v2/screens/${SCREEN}/regions/${REGION_C}/playlists`;

describe("PullStrategy.getRegions with a failed region request", () => {
  beforeEach(() => {
    mockGetAllResultsFromPath.mockReset();
    mockLogger.warn.mockReset();
  });

  it("keeps the region key when its playlists request failed", async () => {
    // getAllResultsFromPath returns a bare {} when the underlying request
    // failed — the region path is then lost and the region silently drops
    // out of regionData, so the client never schedules content for it and
    // the region stays black. See #507.
    mockGetAllResultsFromPath.mockImplementation((regionPath) => {
      if (regionPath === pathA) {
        return Promise.resolve({
          path: pathA,
          results: [{ playlist: { "@id": "/v2/playlists/1", title: "A" } }],
          keys: {},
        });
      }

      return Promise.resolve({});
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    const regionData = await strategy.getRegions([pathA, pathB]);

    expect(Object.keys(regionData).sort()).toEqual([REGION_A, REGION_B].sort());
    expect(regionData[REGION_A]).toHaveLength(1);
    expect(regionData[REGION_B]).toEqual([]);
    expect(mockLogger.warn).toHaveBeenCalled();
  });

  it("keeps the region key when the request rejects", async () => {
    mockGetAllResultsFromPath.mockImplementation((regionPath) => {
      if (regionPath === pathA) {
        return Promise.resolve({ path: pathA, results: [], keys: {} });
      }

      return Promise.reject(new Error("network error"));
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    const regionData = await strategy.getRegions([pathA, pathB]);

    expect(Object.keys(regionData).sort()).toEqual([REGION_A, REGION_B].sort());
  });

  it("keeps the previously loaded playlists when a region request fails", async () => {
    // Stale content beats a black region on signage. A rejected request says
    // nothing about what the region should show, so the last known good
    // playlists stay until a pull actually succeeds.
    const previous = [{ "@id": "/v2/playlists/9", title: "still showing" }];

    mockGetAllResultsFromPath.mockResolvedValue({});

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    strategy.lastestScreenData = { regionData: { [REGION_A]: previous } };

    const regionData = await strategy.getRegions([pathA]);

    expect(regionData[REGION_A]).toEqual(previous);
  });

  it("attaches each region's playlists to the right region when a middle one fails", async () => {
    // Results are matched to regions structurally. If they were matched by
    // position and the list could drift, a region would show another region's
    // playlists — worse than showing nothing.
    mockGetAllResultsFromPath.mockImplementation((regionPath) => {
      if (regionPath === pathB) {
        return Promise.reject(new Error("throttled"));
      }

      const id = regionPath === pathA ? "A" : "C";

      return Promise.resolve({
        path: regionPath,
        results: [{ playlist: { "@id": `/v2/playlists/${id}`, title: id } }],
        keys: {},
      });
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    const regionData = await strategy.getRegions([pathA, pathB, pathC]);

    expect(regionData[REGION_A][0].title).toBe("A");
    expect(regionData[REGION_C][0].title).toBe("C");
    expect(regionData[REGION_B]).toEqual([]);
  });

  it("skips a path that is not a region playlists path", async () => {
    mockGetAllResultsFromPath.mockResolvedValue({ path: pathA, results: [] });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    const regionData = await strategy.getRegions([pathA, "/v2/not-a-region"]);

    expect(Object.keys(regionData)).toEqual([REGION_A]);
  });
});

describe("PullStrategy.getSlidesForRegions", () => {
  const slidesPath = "/v2/playlists/1/slides";

  beforeEach(() => {
    mockGetAllResultsFromPath.mockReset();
    mockLogger.warn.mockReset();
  });

  it("drops a playlist row whose slide relation is absent", async () => {
    // A row with no slide maps to undefined, and getScreen's relations loop
    // writes templateData onto every entry. Assigning to undefined throws, and
    // nothing between there and pull()'s catch handles it, so one broken row
    // aborted the whole pull - the screen kept its last content on every pull
    // after, which is the freeze this whole series of fixes is about (#507).
    mockGetAllResultsFromPath.mockResolvedValue({
      path: slidesPath,
      results: [
        { slide: { "@id": "/v2/slides/a", title: "a" } },
        { slide: null },
        {},
        { slide: { "@id": "/v2/slides/b", title: "b" } },
      ],
      keys: {},
    });

    const strategy = new PullStrategy({ endpoint: "", entryPoint: "" });
    const regionData = await strategy.getSlidesForRegions({
      [REGION_A]: [{ "@id": "/v2/playlists/1", slides: slidesPath }],
    });

    expect(
      regionData[REGION_A][0].slidesData.map((slide) => slide.title),
    ).toEqual(["a", "b"]);
  });
});
