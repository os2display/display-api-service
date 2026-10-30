import { vi } from "vitest";

const { mockGetPath, mockGetAllResultsFromPath, mockLoadConfig, mockLogger } =
  vi.hoisted(() => ({
    mockGetPath: vi.fn(),
    mockGetAllResultsFromPath: vi.fn(),
    mockLoadConfig: vi.fn(),
    mockLogger: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() },
  }));

vi.mock("../../../client/logger/logger", () => ({ default: mockLogger }));

vi.mock("../../../client/util/client-config-loader.js", () => ({
  default: { loadConfig: mockLoadConfig },
}));

vi.mock("../../../client/data-sync/api-helper", () => ({
  default: vi.fn().mockImplementation(function () {
    this.getPath = mockGetPath;
    this.getAllResultsFromPath = mockGetAllResultsFromPath;
  }),
}));

// Imported here so the vi.mock calls above are hoisted above it.
import PullStrategyClass from "../../../client/data-sync/pull-strategy";

// Vitest rewrites references to a hoisted import, but not export specifiers,
// so re-exporting the import binding directly exports undefined.
const PullStrategy = PullStrategyClass;

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

export {
  PullStrategy,
  mockGetPath,
  mockGetAllResultsFromPath,
  mockLoadConfig,
  mockLogger,
  REGION,
  PLAYLIST,
  SLIDE,
  TEMPLATE,
  screenPath,
  layoutPath,
  groupsPath,
  campaignsPath,
  regionPath,
  slidesPath,
  templatePath,
  mediaPath,
  checksums,
  buildScreen,
  buildSlide,
  collection,
  wireApi,
  callsFor,
};
