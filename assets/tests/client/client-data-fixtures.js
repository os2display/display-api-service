/**
 * Helper to create fake JWT tokens for testing.
 * These tokens are parsed by jwt-decode in the browser.
 *
 * @param {object} payload JWT payload.
 * @returns {string} A fake JWT string.
 */
function createFakeJwt(payload) {
  const header = Buffer.from(
    JSON.stringify({ alg: "HS256", typ: "JWT" }),
  ).toString("base64url");
  const body = Buffer.from(JSON.stringify(payload)).toString("base64url");
  return `${header}.${body}.fake_signature`;
}

// --- Config ---

const clientConfigJson = {
  apiEndpoint: "",
  dataStrategy: {
    type: "pull",
    config: {
      interval: 30000,
    },
  },
  loginCheckTimeout: 500,
  configFetchInterval: 900000,
  refreshTokenTimeout: 500,
  releaseTimestampIntervalTimeout: 600000,
  colorScheme: {
    type: "library",
    lat: 56.0,
    lng: 10.0,
  },
  schedulingInterval: 60000,
  debug: false,
  relationsChecksumEnabled: false,
  pullStrategyInterval: 300000,
};

const releaseJson = {
  releaseTimestamp: 12345,
  releaseVersion: "test-1.0.0",
};

// --- Auth ---

const bindKeyResponseJson = {
  status: "awaitingBindKey",
  bindKey: "ABCD-1234",
};

// Short lifetime so ensureFreshToken() triggers immediately in tests.
// timeDiff = 1000, half = 500, current time >> iat + 500 → refresh fires.
const fakeToken = createFakeJwt({ exp: 1700001000, iat: 1700000000 });

const loginReadyResponseJson = {
  status: "ready",
  token: fakeToken,
  refresh_token: "refresh-token-123",
  screenId: "SCREEN01234567890123456789",
  tenantKey: "ABC",
  tenantId: "TENANT01234567890123456789",
};

const refreshedFakeToken = createFakeJwt({ exp: 9999999999, iat: 1700000001 });

const tokenRefreshResponseJson = {
  token: refreshedFakeToken,
  refresh_token: "refresh-token-456",
};

// --- Tenant ---

const tenantJson = {
  "@id": "/v2/tenants/TENANT01234567890123456789",
  "@type": "Tenant",
  tenantKey: "ABC",
  title: "Test Tenant",
  fallbackImageUrl: "https://example.com/fallback.png",
};

// --- Empty hydra collection ---

const emptyHydraJson = {
  "@context": "/contexts/Collection",
  "@type": "hydra:Collection",
  "hydra:totalItems": 0,
  "hydra:member": [],
};

// --- Screen ---

const screenDataJson = {
  "@id": "/v2/screens/SCREEN01234567890123456789",
  "@type": "Screen",
  title: "Test Screen",
  layout: "/v2/layouts/LAYOUT01234567890123456789",
  regions: [
    "/v2/screens/SCREEN01234567890123456789/regions/REGION01234567890123456789/playlists",
  ],
  inScreenGroups: "/v2/screens/SCREEN01234567890123456789/screen-groups",
  campaigns: "/v2/screens/SCREEN01234567890123456789/campaigns",
  enableColorSchemeChange: false,
  relationsChecksum: {},
};

const layoutDataJson = {
  "@id": "/v2/layouts/LAYOUT01234567890123456789",
  "@type": "ScreenLayout",
  title: "Full screen",
  grid: {
    rows: 1,
    columns: 1,
  },
  regions: [
    {
      "@id": "/v2/layouts/regions/REGION01234567890123456789",
      "@type": "ScreenLayoutRegions",
      title: "Full",
      gridArea: ["a"],
    },
  ],
};

const templateDataJson = {
  "@id": "/v2/templates/TMPLTE01234567890123456789",
  "@type": "Template",
  title: "Test Template",
  resources: {
    component: "",
    assets: [],
  },
  css: "",
};

const slideJson = {
  "@id": "/v2/slides/SLIDES01234567890123456789",
  "@type": "Slide",
  title: "Test Slide",
  published: {
    from: "2020-01-01T00:00:00+00:00",
    to: "2099-01-01T00:00:00+00:00",
  },
  templateInfo: {
    "@id": "/v2/templates/TMPLTE01234567890123456789",
  },
  media: [],
  content: {
    text: "Hello World",
  },
  feed: null,
  relationsChecksum: {},
};

const expiredSlideJson = {
  "@id": "/v2/slides/SLIDES02234567890123456789",
  "@type": "Slide",
  title: "Expired Slide",
  published: {
    from: "2020-01-01T00:00:00+00:00",
    to: "2020-06-01T00:00:00+00:00",
  },
  templateInfo: {
    "@id": "/v2/templates/TMPLTE01234567890123456789",
  },
  media: [],
  content: {
    text: "Expired Content",
  },
  feed: null,
  relationsChecksum: {},
};

const futureSlideJson = {
  "@id": "/v2/slides/SLIDES03234567890123456789",
  "@type": "Slide",
  title: "Future Slide",
  published: {
    from: "2099-01-01T00:00:00+00:00",
    to: "2099-06-01T00:00:00+00:00",
  },
  templateInfo: {
    "@id": "/v2/templates/TMPLTE01234567890123456789",
  },
  media: [],
  content: {
    text: "Future Content",
  },
  feed: null,
  relationsChecksum: {},
};

const playlistJson = {
  "@id": "/v2/playlists/PLAYLT01234567890123456789",
  "@type": "Playlist",
  title: "Test Playlist",
  schedules: [],
  published: {
    from: "2020-01-01T00:00:00+00:00",
    to: "2099-01-01T00:00:00+00:00",
  },
  slides: "/v2/playlists/PLAYLT01234567890123456789/slides",
};

const regionPlaylistsJson = {
  "@context": "/contexts/PlaylistScreenRegion",
  "@type": "hydra:Collection",
  "hydra:totalItems": 1,
  "hydra:member": [
    {
      playlist: playlistJson,
    },
  ],
};

const playlistSlidesJson = {
  "@context": "/contexts/PlaylistSlide",
  "@type": "hydra:Collection",
  "hydra:totalItems": 1,
  "hydra:member": [
    {
      slide: slideJson,
    },
  ],
};

// --- Multi-region layout (2 columns, 1 row) ---

const multiRegionLayoutDataJson = {
  "@id": "/v2/layouts/LAYOUT02234567890123456789",
  "@type": "ScreenLayout",
  title: "Two column layout",
  grid: {
    rows: 1,
    columns: 2,
  },
  regions: [
    {
      "@id": "/v2/layouts/regions/REGION01234567890123456789",
      "@type": "ScreenLayoutRegions",
      title: "Left",
      gridArea: ["a"],
    },
    {
      "@id": "/v2/layouts/regions/REGION02234567890123456789",
      "@type": "ScreenLayoutRegions",
      title: "Right",
      gridArea: ["b"],
    },
  ],
};

const multiRegionScreenDataJson = {
  "@id": "/v2/screens/SCREEN01234567890123456789",
  "@type": "Screen",
  title: "Multi Region Screen",
  layout: "/v2/layouts/LAYOUT02234567890123456789",
  regions: [
    "/v2/screens/SCREEN01234567890123456789/regions/REGION01234567890123456789/playlists",
    "/v2/screens/SCREEN01234567890123456789/regions/REGION02234567890123456789/playlists",
  ],
  inScreenGroups: "/v2/screens/SCREEN01234567890123456789/screen-groups",
  campaigns: "/v2/screens/SCREEN01234567890123456789/campaigns",
  enableColorSchemeChange: false,
  relationsChecksum: {},
};

// --- Error responses ---

const error401Json = {
  code: 401,
  message: "Unauthorized",
};

const error500Json = {
  code: 500,
  message: "Internal Server Error",
};

export {
  createFakeJwt,
  clientConfigJson,
  releaseJson,
  bindKeyResponseJson,
  loginReadyResponseJson,
  fakeToken,
  refreshedFakeToken,
  tokenRefreshResponseJson,
  tenantJson,
  emptyHydraJson,
  screenDataJson,
  layoutDataJson,
  templateDataJson,
  slideJson,
  expiredSlideJson,
  futureSlideJson,
  playlistJson,
  regionPlaylistsJson,
  playlistSlidesJson,
  multiRegionLayoutDataJson,
  multiRegionScreenDataJson,
  error401Json,
  error500Json,
};
