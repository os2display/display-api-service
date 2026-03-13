import { test, expect } from "@playwright/test";
import {
  bindKeyResponseJson,
  loginReadyResponseJson,
  tokenRefreshResponseJson,
  error500Json,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  fulfillDataRoute,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client error handling", () => {
  test("Status and error codes appear in URL params", async ({ page }) => {
    await clientBeforeEachTest(page);

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
    // Mock config to return 500 — ClientConfigLoader falls back to defaults.
    await page.route("**/config/client", async (route) => {
      await route.fulfill({ json: error500Json, status: 500 });
    });

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

    // The app should still start using default config values.
    await expect(page.locator(".bind-key")).toBeVisible({ timeout: 30000 });
    await expect(page.locator(".bind-key")).toHaveText("ABCD-1234");
  });

  test("Missing screen data shows fallback", async ({ page }) => {
    await clientBeforeEachTest(page);

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
});
