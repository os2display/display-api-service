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

  // Verifies that an unregistered screen shows its bind key.
  // Mock: auth endpoint returns "awaitingBindKey" status with a bind key value.
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

  // Verifies the full bind-key → registered → running transition.
  // Mock: auth endpoint returns bind key on first call, then "ready" on subsequent calls.
  // The app re-checks login after loginCheckTimeout (500ms in test config).
  test("Transitions from bind key to running", async ({ page }) => {
    let authCallCount = 0;
    await page.route("**/v2/authentication/screen", async (route) => {
      authCallCount += 1;
      if (authCallCount === 1) {
        await route.fulfill({ json: bindKeyResponseJson });
      } else {
        await route.fulfill({ json: loginReadyResponseJson });
      }
    });

    await fulfillDataRoute(
      page,
      "**/v2/authentication/token/refresh",
      tokenRefreshResponseJson,
    );

    await mockContentPipeline(page);

    await gotoClient(page);

    // Bind key is shown initially.
    await expect(page.locator(".bind-key")).toBeVisible();
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");

    // After login re-check: bind key disappears, screen renders.
    await expect(page.locator(".bind-key")).not.toBeVisible({ timeout: 10000 });
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that a successful login with no content shows the fallback image.
  // Mock: login succeeds but playlist has zero slides → contentEmpty event → fallback.
  test("Shows fallback when content is empty", async ({ page }) => {
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

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that token refresh fires during normal operation.
  // The fake JWT has a short lifetime (iat=1700000000, exp=1700001000) so the
  // midpoint is in the past — ensureFreshToken() triggers refresh immediately.
  test("Handles token refresh", async ({ page }) => {
    await mockScreenLogin(page);

    const refreshRequest = page.waitForRequest(
      "**/v2/authentication/token/refresh",
    );

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Refresh endpoint must have been called.
    await refreshRequest;
  });

  // Verifies that a 401 from the screen API prevents rendering.
  // Mock: login succeeds, but screen data endpoint returns 401.
  // The 401 route is registered AFTER mockScreenLogin so it wins (LIFO).
  test("Handles 401 from API gracefully", async ({ page }) => {
    await mockScreenLogin(page);

    await page.route(
      /\/v2\/screens\/[A-Za-z0-9]{26}(\?.*)?$/,
      async (route) => {
        await route.fulfill({ json: { code: 401 }, status: 401 });
      },
    );

    await gotoClient(page);

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
    await expect(page.locator(".screen")).not.toBeVisible();
  });
});
