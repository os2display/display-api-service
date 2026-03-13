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

  // Verifies that StatusService writes the "status" param to the URL.
  // After the app initializes, the current status (e.g. "login") is reflected
  // in ?status= via window.history.replaceState.
  test("Status and error codes appear in URL params", async ({ page }) => {
    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    await expect(page.locator(".bind-key")).toBeVisible();

    const url = new URL(page.url());
    expect(url.searchParams.get("status")).toBeTruthy();
  });

  // Verifies that /config/client returning 500 doesn't break the app.
  // ClientConfigLoader catches fetch errors and returns default config values,
  // so the app still starts and shows the bind key.
  test("Config load failure uses defaults and app still starts", async ({
    page,
  }) => {
    await page.route("**/config/client", async (route) => {
      await route.fulfill({ json: error500Json, status: 500 });
    });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 30000 });
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");
  });

  // Verifies that a 500 from the screen data endpoint shows the fallback.
  // Login succeeds, but pull-strategy's getScreen call fails → no screen data
  // → no Screen component rendered → fallback image shown.
  test("Missing screen data shows fallback", async ({ page }) => {
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

    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: error500Json, status: 500 });
      },
    );

    await page.route("**/v2/tenants/*", async (route) => {
      await route.fulfill({ json: { fallbackImageUrl: null } });
    });

    await gotoClient(page);

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
    await expect(page.locator(".screen")).not.toBeVisible();
  });

  // ER101: API returns 401 → api-helper dispatches "reauthenticate" event →
  // app.jsx tries refreshToken() → refresh fails → ER101 set in URL.
  // Mock: auth returns "ready" first (starts content), then "bindKey" (after
  // storage is cleared). Screen endpoint always returns 401. Refresh always fails.
  test("ER101: 401 from API triggers reauthenticate and sets error code", async ({
    page,
  }) => {
    let authCallCount = 0;
    await page.route("**/v2/authentication/screen", async (route) => {
      authCallCount += 1;
      if (authCallCount === 1) {
        await route.fulfill({ json: loginReadyResponseJson });
      } else {
        await route.fulfill({ json: bindKeyResponseJson });
      }
    });

    await page.route("**/v2/authentication/token/refresh", async (route) => {
      await route.fulfill({ json: { code: 401 }, status: 401 });
    });

    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: { code: 401 }, status: 401 });
      },
    );

    await page.route("**/v2/tenants/*", async (route) => {
      await route.fulfill({ json: { fallbackImageUrl: null } });
    });

    await gotoClient(page);

    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 15000 });

    await expect
      .poll(() => {
        const url = new URL(page.url());
        return url.searchParams.get("error");
      })
      .toBe("ER101");
  });

  // ER102: ensureFreshToken() calls refreshToken() → promise rejects → ER102.
  // Mock: config with 200ms refresh interval so the check fires quickly.
  // Token refresh endpoint returns 500 (LIFO override of mockScreenLogin's route).
  test("ER102: Token refresh loop failure sets error code", async ({
    page,
  }) => {
    const fastRefreshConfig = {
      ...clientConfigJson,
      refreshTokenTimeout: 200,
    };
    await page.route("**/config/client", async (route) => {
      await route.fulfill({ json: fastRefreshConfig });
    });

    await mockScreenLogin(page);

    // Override refresh to fail (LIFO priority over mockScreenLogin's handler).
    await page.route("**/v2/authentication/token/refresh", async (route) => {
      await route.fulfill({
        status: 500,
        json: { code: 500, message: "Server Error" },
      });
    });

    await gotoClient(page);

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

  // ER104: release.json returns null releaseTimestamp → ReleaseService sets ER104.
  // Mock: override release.json (LIFO over clientBeforeEachTest) to return nulls.
  test("ER104: Release file failure sets error code", async ({ page }) => {
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

    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 15000 });

    const url = new URL(page.url());
    expect(url.searchParams.get("error")).toBe("ER104");
  });

  // ER105: checkToken() on mount finds nowSeconds > expire → ER105.
  // Approach: navigate, inject an expired token into localStorage, reload.
  // The expired JWT has exp=1000000000 (2001), which is well in the past.
  test("ER105: Expired token sets error code on boot", async ({ page }) => {
    const expiredToken = createFakeJwt({ exp: 1000000000, iat: 999999000 });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    await page.evaluate(
      ({ token }) => {
        localStorage.setItem("apiToken", token);
        localStorage.setItem("apiTokenExpire", "1000000000");
        localStorage.setItem("apiTokenIssuedAt", "999999000");
      },
      { token: expiredToken },
    );

    // Reload triggers checkToken() which reads the expired token from storage.
    await page.reload();

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

  // ER106: checkToken() finds nowSeconds > iat + (exp-iat)/2 but < exp → ER106.
  // Approach: inject a token whose midpoint (2017) is in the past but exp (2033)
  // is still in the future. Reload triggers checkToken().
  test("ER106: Token past refresh midpoint sets error code on boot", async ({
    page,
  }) => {
    // midpoint = iat + (exp-iat)/2 = 1000000000 + 500000000 = 1500000000 (2017)
    const midpointToken = createFakeJwt({
      exp: 2000000000,
      iat: 1000000000,
    });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    await page.evaluate(
      ({ token }) => {
        localStorage.setItem("apiToken", token);
        localStorage.setItem("apiTokenExpire", "2000000000");
        localStorage.setItem("apiTokenIssuedAt", "1000000000");
      },
      { token: midpointToken },
    );

    // Reload triggers checkToken() which sees the token past its midpoint.
    await page.reload();

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
