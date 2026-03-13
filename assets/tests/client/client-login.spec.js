import { test, expect } from "@playwright/test";
import {
  bindKeyResponseJson,
  loginReadyResponseJson,
  tokenRefreshResponseJson,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  fulfillDataRoute,
  mockScreenLogin,
  mockContentPipeline,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client login and auth flow", () => {
  test.beforeEach(async ({ page }) => {
    await clientBeforeEachTest(page);
  });

  test("Displays bind key while awaiting registration", async ({ page }) => {
    await fulfillDataRoute(
      page,
      "**/v2/authentication/screen",
      bindKeyResponseJson,
    );

    await gotoClient(page);

    await expect(page.locator(".bind-key")).toBeVisible();
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");
  });

  test("Transitions from bind key to running", async ({ page }) => {
    // First call returns bind key, subsequent calls return login ready.
    let authCallCount = 0;
    await page.route("**/v2/authentication/screen", async (route) => {
      authCallCount += 1;
      if (authCallCount === 1) {
        await route.fulfill({ json: bindKeyResponseJson });
      } else {
        await route.fulfill({ json: loginReadyResponseJson });
      }
    });

    // Mock token refresh (needed after login succeeds).
    await fulfillDataRoute(
      page,
      "**/v2/authentication/token/refresh",
      tokenRefreshResponseJson,
    );

    // Mock content pipeline for after login succeeds.
    await mockContentPipeline(page);

    await gotoClient(page);

    // First: bind key is shown.
    await expect(page.locator(".bind-key")).toBeVisible();
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");

    // After re-check (loginCheckTimeout=500ms): bind key disappears and screen renders.
    await expect(page.locator(".bind-key")).not.toBeVisible({ timeout: 10000 });
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });

  test("Shows fallback when content is empty", async ({ page }) => {
    // Login succeeds but the playlist has no slides → empty content → fallback.
    const emptyPlaylistSlidesJson = {
      "@context": "/contexts/PlaylistSlide",
      "@type": "hydra:Collection",
      "hydra:totalItems": 0,
      "hydra:member": [],
    };

    await mockScreenLogin(page, {
      playlistSlides: emptyPlaylistSlidesJson,
    });

    await gotoClient(page);

    // Fallback should be visible since there is no scheduled content.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  test("Handles token refresh", async ({ page }) => {
    await mockScreenLogin(page);

    // Track that the refresh endpoint is called.
    const refreshRequest = page.waitForRequest(
      "**/v2/authentication/token/refresh",
    );

    await gotoClient(page);

    // Wait for the app to reach running state.
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Token refresh should be triggered (fake token has old iat).
    await refreshRequest;
  });

  test("Handles 401 from API gracefully", async ({ page }) => {
    await mockScreenLogin(page);

    // Override screen endpoint to return 401 (registered after mockScreenLogin,
    // so it takes priority due to LIFO route matching).
    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: { code: 401 }, status: 401 });
      },
    );

    await gotoClient(page);

    // App should show fallback (screen data never loaded due to 401).
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });

    // Screen component should not render since no screen data was received.
    await expect(page.locator(".screen")).not.toBeVisible();
  });
});
