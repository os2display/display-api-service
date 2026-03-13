import {
  clientConfigJson,
  releaseJson,
  loginReadyResponseJson,
  tokenRefreshResponseJson,
  tenantJson,
  screenDataJson,
  layoutDataJson,
  regionPlaylistsJson,
  playlistSlidesJson,
  templateDataJson,
  emptyHydraJson,
} from "./client-data-fixtures.js";

/**
 * Standard client beforeEach setup.
 * Mocks config and release endpoints.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {object} options Optional overrides.
 * @param {object} options.config Override client config response.
 * @param {object} options.release Override release.json response.
 */
const clientBeforeEachTest = async (page, options = {}) => {
  const { config = clientConfigJson, release = releaseJson } = options;

  await page.route("**/config/client", async (route) => {
    await route.fulfill({ json: config });
  });

  await page.route("**/release.json*", async (route) => {
    await route.fulfill({ json: release });
  });
};

/**
 * Fulfill a route with the given data and optional status.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {string|RegExp} routePattern The URL pattern.
 * @param {object} data The response JSON.
 * @param {number} [status] Optional HTTP status code.
 */
const fulfillDataRoute = async (page, routePattern, data, status) => {
  const result = { json: data };

  if (status) {
    result.status = status;
  }

  await page.route(routePattern, async (route) => {
    await route.fulfill(result);
  });
};

/**
 * Fulfill multiple routes with empty hydra collections.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {Array<string|RegExp>} routePatterns The URL patterns.
 */
const fulfillEmptyRoutes = async (page, routePatterns) => {
  for (const routePattern of routePatterns) {
    await page.route(routePattern, async (route) => {
      await route.fulfill({ json: emptyHydraJson });
    });
  }
};

/**
 * Mock the complete content pipeline routes (screen data, layout, regions,
 * playlists, slides, templates).
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {object} options Optional overrides.
 */
const mockContentPipeline = async (page, options = {}) => {
  const {
    screen = screenDataJson,
    layout = layoutDataJson,
    regionPlaylists = regionPlaylistsJson,
    playlistSlides = playlistSlidesJson,
    template = templateDataJson,
    tenant = tenantJson,
  } = options;

  // Screen endpoint — regex ensures exact path match (no subpaths).
  await page.route(
    /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
    async (route) => {
      await route.fulfill({ json: screen });
    },
  );

  // Screen groups (empty).
  await page.route("**/v2/screens/*/screen-groups*", async (route) => {
    await route.fulfill({ json: emptyHydraJson });
  });

  // Screen campaigns (empty).
  await page.route("**/v2/screens/*/campaigns*", async (route) => {
    await route.fulfill({ json: emptyHydraJson });
  });

  // Layout — glob * does not match /, so this only matches the layout path itself.
  await page.route("**/v2/layouts/LAYOUT*", async (route) => {
    await route.fulfill({ json: layout });
  });

  // Region playlists.
  await page.route(
    "**/v2/screens/*/regions/*/playlists*",
    async (route) => {
      await route.fulfill({ json: regionPlaylists });
    },
  );

  // Playlist slides.
  await page.route("**/v2/playlists/*/slides*", async (route) => {
    await route.fulfill({ json: playlistSlides });
  });

  // Template.
  await page.route("**/v2/templates/*", async (route) => {
    await route.fulfill({ json: template });
  });

  // Tenant.
  await page.route("**/v2/tenants/*", async (route) => {
    await route.fulfill({ json: tenant });
  });
};

/**
 * Mock the full screen login flow and content pipeline.
 * Sets up routes for a successful login followed by screen content loading.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {object} options Optional overrides.
 */
const mockScreenLogin = async (page, options = {}) => {
  const {
    loginResponse = loginReadyResponseJson,
    tokenRefresh = tokenRefreshResponseJson,
    ...contentOptions
  } = options;

  // Auth endpoint.
  await page.route("**/v2/authentication/screen", async (route) => {
    await route.fulfill({ json: loginResponse });
  });

  // Token refresh.
  await page.route(
    "**/v2/authentication/token/refresh",
    async (route) => {
      await route.fulfill({ json: tokenRefresh });
    },
  );

  // Content pipeline routes.
  await mockContentPipeline(page, contentOptions);
};

/**
 * Navigate to the client app with the releaseTimestamp param
 * pre-set to avoid release redirect.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 * @param {object} [params] Additional URL params.
 * @returns {Promise<void>}
 */
const gotoClient = async (page, params = {}) => {
  const url = new URL("/client", "http://localhost");
  url.searchParams.set("releaseTimestamp", "12345");

  for (const [key, value] of Object.entries(params)) {
    url.searchParams.set(key, value);
  }

  await page.goto(`/client${url.search}`);
};

export {
  clientBeforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  mockContentPipeline,
  mockScreenLogin,
  gotoClient,
};
