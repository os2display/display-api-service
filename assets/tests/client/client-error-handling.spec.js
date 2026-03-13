import { test, expect } from "@playwright/test";
import {
  createFakeJwt,
  clientConfigJson,
  bindKeyResponseJson,
  loginReadyResponseJson,
  tokenRefreshResponseJson,
  error500Json,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  fulfillDataRoute,
  mockScreenLogin,
  mockContentPipeline,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client error handling", () => {
  test.beforeEach(async ({ page }) => {
    await clientBeforeEachTest(page);
  });

  test("Status and error codes appear in URL params", async ({ page }) => {
    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    // Wait for the app to initialize and set status in URL.
    await expect(page.locator(".bind-key")).toBeVisible();

    // StatusService should have set the status param in the URL.
    const url = new URL(page.url());
    expect(url.searchParams.get("status")).toBeTruthy();
  });

  test("Config load failure uses defaults and app still starts", async ({
    page,
  }) => {
    // Override config to return 500 — ClientConfigLoader falls back to defaults.
    await page.route("**/config/client", async (route) => {
      await route.fulfill({ json: error500Json, status: 500 });
    });

    // release.json is already mocked by clientBeforeEachTest, but the config
    // route registered here takes priority (LIFO). We still need auth mocked.
    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    // The app should still start using default config values.
    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 30000 });
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");
  });

  test("Missing screen data shows fallback", async ({ page }) => {
    // Login succeeds.
    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      loginReadyResponseJson,
    );

    await fulfillDataRoute(
      page,
      "**/v2/authentication/token/refresh",
      tokenRefreshResponseJson,
    );

    // Screen endpoint returns 500 — getScreen aborts content update.
    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: error500Json, status: 500 });
      },
    );

    // Tenant endpoint.
    await page.route("**/v2/tenants/*", async (route) => {
      await route.fulfill({ json: { fallbackImageUrl: null } });
    });

    await gotoClient(page);

    // Fallback should show since screen data failed to load.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });

    // Screen component should not render.
    await expect(page.locator(".screen")).not.toBeVisible();
  });

  // ER101: API returns 401, token could not be refreshed.
  test("ER101: 401 from API triggers reauthenticate and sets error code", async ({
    page,
  }) => {
    // First auth call returns login ready (triggers content load).
    // Subsequent calls return bind key (after reauthenticate clears storage).
    let authCallCount = 0;
    await page.route("**/v2/authentication/screen", async (route) => {
      authCallCount += 1;
      if (authCallCount === 1) {
        await route.fulfill({ json: loginReadyResponseJson });
      } else {
        await route.fulfill({ json: bindKeyResponseJson });
      }
    });

    // Token refresh always fails (so reauthenticateHandler sets ER101).
    await page.route(
      "**/v2/authentication/token/refresh",
      async (route) => {
        await route.fulfill({ json: { code: 401 }, status: 401 });
      },
    );

    // Content pipeline: screen endpoint returns 401 (triggers reauthenticate event).
    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: { code: 401 }, status: 401 });
      },
    );

    // Tenant endpoint.
    await page.route("**/v2/tenants/*", async (route) => {
      await route.fulfill({ json: { fallbackImageUrl: null } });
    });

    await gotoClient(page);

    // After reauthenticate: refresh fails → ER101 set → storage cleared →
    // checkLogin() → second auth call returns bind key.
    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 15000 });

    // ER101 should appear in the URL error param.
    await expect
      .poll(() => {
        const url = new URL(page.url());
        return url.searchParams.get("error");
      })
      .toBe("ER101");
  });

  // ER102: Token could not be refreshed in normal refresh token loop.
  test("ER102: Token refresh loop failure sets error code", async ({
    page,
  }) => {
    // Use a config with very short refresh interval to trigger ensureFreshToken quickly.
    const fastRefreshConfig = {
      ...clientConfigJson,
      refreshTokenTimeout: 200,
    };
    await page.route("**/config/client", async (route) => {
      await route.fulfill({ json: fastRefreshConfig });
    });

    await mockScreenLogin(page);

    // Override token refresh to fail (LIFO priority over mockScreenLogin).
    // When ensureFreshToken() calls refreshToken() and it rejects, ER102 is set.
    await page.route(
      "**/v2/authentication/token/refresh",
      async (route) => {
        await route.fulfill({
          status: 500,
          json: { code: 500, message: "Server Error" },
        });
      },
    );

    await gotoClient(page);

    // Wait for the refresh interval to fire and the error to be set.
    await expect
      .poll(
        () => {
          const url = new URL(page.url());
          return url.searchParams.get("error");
        },
        { timeout: 10000 },
      )
      .toBe("ER102");
  });

  // ER104: Release file could not be loaded.
  test("ER104: Release file failure sets error code", async ({ page }) => {
    // Override release.json to return null timestamp (simulates load failure).
    // This must be registered AFTER clientBeforeEachTest for LIFO priority.
    await page.route("**/release.json*", async (route) => {
      await route.fulfill({
        json: { releaseTimestamp: null, releaseVersion: null },
      });
    });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    // Wait for the app to set the error.
    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 15000 });

    // ER104 should appear in URL.
    const url = new URL(page.url());
    expect(url.searchParams.get("error")).toBe("ER104");
  });

  // ER105: Token is expired.
  test("ER105: Expired token sets error code on boot", async ({ page }) => {
    // Pre-populate localStorage with an expired token before navigating.
    // checkToken() runs on mount and checks if nowSeconds > expire.
    const expiredToken = createFakeJwt({ exp: 1000000000, iat: 999999000 });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    // Navigate first, then set localStorage and reload to trigger checkToken.
    await gotoClient(page);

    // Set localStorage with the expired token data.
    await page.evaluate(
      ({ token }) => {
        localStorage.setItem("apiToken", token);
        localStorage.setItem("apiTokenExpire", "1000000000");
        localStorage.setItem("apiTokenIssuedAt", "999999000");
      },
      { token: expiredToken },
    );

    // Reload — checkToken() will see expired token on mount.
    await page.reload();

    // ER105 should appear in URL error param.
    await expect
      .poll(
        () => {
          const url = new URL(page.url());
          return url.searchParams.get("error");
        },
        { timeout: 10000 },
      )
      .toBe("ER105");
  });

  // ER106: Token is valid but should have been refreshed.
  test("ER106: Token past refresh midpoint sets error code on boot", async ({
    page,
  }) => {
    // Token is not expired but past the halfway point.
    // exp far in future, iat in the past, but nowSeconds > iat + timeDiff/2.
    const midpointToken = createFakeJwt({
      exp: 2000000000,
      iat: 1000000000,
    });
    // timeDiff = 1000000000, half = 500000000
    // midpoint = 1000000000 + 500000000 = 1500000000 (2017-07-14)
    // current time (2026) >> 1500000000 → ER106

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    // Set localStorage with the midpoint-past token.
    await page.evaluate(
      ({ token }) => {
        localStorage.setItem("apiToken", token);
        localStorage.setItem("apiTokenExpire", "2000000000");
        localStorage.setItem("apiTokenIssuedAt", "1000000000");
      },
      { token: midpointToken },
    );

    // Reload — checkToken() will see token past midpoint on mount.
    await page.reload();

    // ER106 should appear in URL error param.
    await expect
      .poll(
        () => {
          const url = new URL(page.url());
          return url.searchParams.get("error");
        },
        { timeout: 10000 },
      )
      .toBe("ER106");
  });
});
