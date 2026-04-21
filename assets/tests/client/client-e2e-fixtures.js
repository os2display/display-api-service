import { clientConfigJson } from "../admin/data-fixtures.js";

// --- IDs (must be exactly 26 alphanumeric chars for idFromPath regex) ---
const SCREEN_ID = "SCREEN0001AAAAAAAAAAAAAAAA";
const LAYOUT_ID = "LAYOUT0001AAAAAAAAAAAAAAAA";
const REGION_ID = "REGION0001AAAAAAAAAAAAAAAA";
const PLAYLIST_1_ID = "PLAYLS0001AAAAAAAAAAAAAAAA";
const PLAYLIST_2_ID = "PLAYLS0002AAAAAAAAAAAAAAAA";
const SLIDE_1_ID = "SLIDES0001AAAAAAAAAAAAAAAA";
const SLIDE_2_ID = "SLIDES0002AAAAAAAAAAAAAAAA";
const SLIDE_3_ID = "SLIDES0003AAAAAAAAAAAAAAAA";
const TEMPLATE_ID = "01FP2SNGFN0BZQH03KCBXHKYHG"; // Must match image-text.json id
const MEDIA_1_ID = "MEDIAS0001AAAAAAAAAAAAAAAA";
const MEDIA_2_ID = "MEDIAS0002AAAAAAAAAAAAAAAA";
const TENANT_ID = "TENANT0001AAAAAAAAAAAAAAAA";

// Minimal JWT with exp in 2099. jwt-decode only decodes, no signature check.
// Header: {"alg":"none","typ":"JWT"} Payload: {"iat":1700000000,"exp":4102444800}
const TEST_JWT_TOKEN =
  "eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJpYXQiOjE3MDAwMDAwMDAsImV4cCI6NDEwMjQ0NDgwMH0.";

const releaseJson = {
  releaseTimestamp: null,
  releaseVersion: null,
  releaseTime: null,
};

const loginReadyJson = {
  status: "ready",
  token: TEST_JWT_TOKEN,
  refresh_token: "test-refresh-token",
  screenId: SCREEN_ID,
  tenantKey: "TestTenantKey",
  tenantId: TENANT_ID,
};

const loginBindKeyJson = {
  status: "awaitingBindKey",
  bindKey: "TEST-BIND-KEY",
};

const screenJson = {
  "@id": `/v2/screens/${SCREEN_ID}`,
  layout: `/v2/layouts/${LAYOUT_ID}`,
  regions: [`/v2/screens/${SCREEN_ID}/regions/${REGION_ID}/playlists`],
  relationsChecksum: {
    campaigns: "aaa",
    inScreenGroups: "bbb",
    layout: "ccc",
    regions: "ddd",
  },
};

const layoutJson = {
  "@id": `/v2/layouts/${LAYOUT_ID}`,
  grid: { rows: 1, columns: 1 },
  regions: [{ "@id": `/v2/layouts/regions/${REGION_ID}`, gridArea: ["a"] }],
};

const regionPlaylistsJson = {
  "hydra:member": [
    {
      playlist: {
        "@id": `/v2/playlists/${PLAYLIST_1_ID}`,
        title: "Playlist 1",
        published: { from: "2020-01-01T00:00:00.000Z" },
        schedules: [],
        slides: `/v2/playlists/${PLAYLIST_1_ID}/slides`,
      },
    },
    {
      playlist: {
        "@id": `/v2/playlists/${PLAYLIST_2_ID}`,
        title: "Playlist 2",
        published: { from: "2020-01-01T00:00:00.000Z" },
        schedules: [],
        slides: `/v2/playlists/${PLAYLIST_2_ID}/slides`,
      },
    },
  ],
  "hydra:totalItems": 2,
};

function makeSlide(id, mediaIds, title, text, checksumSuffix) {
  return {
    "@id": `/v2/slides/${id}`,
    templateInfo: { "@id": `/v2/templates/${TEMPLATE_ID}` },
    media: mediaIds.map((mid) => `/v2/media/${mid}`),
    content: {
      duration: 2000,
      title,
      text,
      boxAlign: "left",
      fontSize: "font-size-m",
    },
    published: { from: "2020-01-01T00:00:00.000Z" },
    relationsChecksum: {
      templateInfo: `t${checksumSuffix}`,
      media: `m${checksumSuffix}`,
    },
  };
}

const playlist1SlidesJson = {
  "hydra:member": [
    { slide: makeSlide(SLIDE_1_ID, [MEDIA_1_ID], "Slide 1 Title", "Slide 1 text", "1") },
    { slide: makeSlide(SLIDE_2_ID, [MEDIA_2_ID], "Slide 2 Title", "Slide 2 text", "2") },
  ],
  "hydra:totalItems": 2,
};

const playlist2SlidesJson = {
  "hydra:member": [
    { slide: makeSlide(SLIDE_3_ID, [], "Slide 3 Title", "Slide 3 text", "3") },
  ],
  "hydra:totalItems": 1,
};

const templateJson = {
  "@id": `/v2/templates/${TEMPLATE_ID}`,
  id: TEMPLATE_ID,
  resources: {},
};

const media1Json = {
  "@id": `/v2/media/${MEDIA_1_ID}`,
  assets: { uri: "/fixtures/template/images/mountain1.jpeg" },
};

const media2Json = {
  "@id": `/v2/media/${MEDIA_2_ID}`,
  assets: { uri: "/fixtures/template/images/mountain2.jpeg" },
};

const tenantJson = {
  "@id": `/v2/tenants/${TENANT_ID}`,
  fallbackImageUrl: null,
};

const emptyHydraJson = {
  "hydra:member": [],
  "hydra:totalItems": 0,
};

// Short login check timeout for bind-key test (2 seconds).
const clientConfigShortLoginJson = {
  ...clientConfigJson,
  loginCheckTimeout: 2000,
};

export {
  SCREEN_ID,
  LAYOUT_ID,
  REGION_ID,
  PLAYLIST_1_ID,
  PLAYLIST_2_ID,
  TEMPLATE_ID,
  MEDIA_1_ID,
  MEDIA_2_ID,
  TENANT_ID,
  releaseJson,
  clientConfigJson,
  clientConfigShortLoginJson,
  loginReadyJson,
  loginBindKeyJson,
  screenJson,
  layoutJson,
  regionPlaylistsJson,
  playlist1SlidesJson,
  playlist2SlidesJson,
  templateJson,
  media1Json,
  media2Json,
  tenantJson,
  emptyHydraJson,
};
