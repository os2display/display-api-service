import { test, expect } from "@playwright/test";
import {
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
} from "./client-e2e-fixtures.js";

/**
 * Register all route mocks for the client application.
 *
 * Catch-all abort is registered first; specific routes registered after take
 * priority (Playwright matches in LIFO order).
 */
async function setupClientRoutes(page, configOverride = null) {
  // Catch-all: abort unregistered fetch/XHR requests (API calls).
  // Document, script, stylesheet, and image requests pass through to the server.
  await page.route("**/*", async (route) => {
    const type = route.request().resourceType();
    if (type === "fetch" || type === "xhr") {
      await route.abort();
    } else {
      await route.continue();
    }
  });

  // Release check.
  await page.route("**/release.json*", async (route) => {
    await route.fulfill({ json: releaseJson });
  });

  // Client config.
  await page.route("**/config/client", async (route) => {
    await route.fulfill({ json: configOverride ?? clientConfigJson });
  });

  // Screen authentication (POST only).
  await page.route("**/v2/authentication/screen", async (route) => {
    if (route.request().method() === "POST") {
      await route.fulfill({ json: loginReadyJson });
    } else {
      await route.abort();
    }
  });

  // Screen data.
  await page.route(`**/v2/screens/${SCREEN_ID}`, async (route) => {
    await route.fulfill({ json: screenJson });
  });

  // Layout.
  await page.route(`**/v2/layouts/${LAYOUT_ID}`, async (route) => {
    await route.fulfill({ json: layoutJson });
  });

  // Region playlists.
  await page.route(
    `**/v2/screens/${SCREEN_ID}/regions/${REGION_ID}/playlists*`,
    async (route) => {
      await route.fulfill({ json: regionPlaylistsJson });
    },
  );

  // Playlist 1 slides.
  await page.route(`**/v2/playlists/${PLAYLIST_1_ID}/slides*`, async (route) => {
    await route.fulfill({ json: playlist1SlidesJson });
  });

  // Playlist 2 slides.
  await page.route(`**/v2/playlists/${PLAYLIST_2_ID}/slides*`, async (route) => {
    await route.fulfill({ json: playlist2SlidesJson });
  });

  // Template.
  await page.route(`**/v2/templates/${TEMPLATE_ID}`, async (route) => {
    await route.fulfill({ json: templateJson });
  });

  // Media.
  await page.route(`**/v2/media/${MEDIA_1_ID}`, async (route) => {
    await route.fulfill({ json: media1Json });
  });
  await page.route(`**/v2/media/${MEDIA_2_ID}`, async (route) => {
    await route.fulfill({ json: media2Json });
  });

  // Campaigns (empty).
  await page.route(`**/v2/screens/${SCREEN_ID}/campaigns*`, async (route) => {
    await route.fulfill({ json: emptyHydraJson });
  });

  // Screen groups (empty).
  await page.route(
    `**/v2/screens/${SCREEN_ID}/screen-groups*`,
    async (route) => {
      await route.fulfill({ json: emptyHydraJson });
    },
  );

  // Tenant.
  await page.route(`**/v2/tenants/${TENANT_ID}`, async (route) => {
    await route.fulfill({ json: tenantJson });
  });
}

test.describe("Client E2E: login, playlists, slide progression", () => {
  test("Login with bind key then authentication", async ({ page }) => {
    // Use short login timeout so the bind-key -> ready transition is fast.
    await setupClientRoutes(page, clientConfigShortLoginJson);

    // Override auth to first return bind key.
    let loginCallCount = 0;
    await page.unroute("**/v2/authentication/screen");
    await page.route("**/v2/authentication/screen", async (route) => {
      if (route.request().method() !== "POST") {
        await route.abort();
        return;
      }

      loginCallCount += 1;

      if (loginCallCount <= 1) {
        await route.fulfill({ json: loginBindKeyJson });
      } else {
        await route.fulfill({ json: loginReadyJson });
      }
    });

    await page.goto("/client");

    // Bind key should be displayed.
    await expect(page.locator(".bind-key")).toHaveText("TEST-BIND-KEY");

    // After the next login poll, screen should appear.
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });

  test("Screen renders with region and slides from multiple playlists", async ({
    page,
  }) => {
    await setupClientRoutes(page);
    await page.goto("/client");

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
    await expect(page.locator(".region")).toBeVisible();
    await expect(page.locator(".slide")).toBeVisible();
    await expect(page.locator(".template-image-text")).toBeVisible();
    await expect(page.locator(".template-image-text h1")).toHaveText(
      "Slide 1 Title",
    );
  });

  test("slideDone is called - slides transition after duration", async ({
    page,
  }) => {
    await setupClientRoutes(page);
    await page.goto("/client");

    // Wait for first slide.
    await expect(page.locator(".template-image-text h1")).toHaveText(
      "Slide 1 Title",
      { timeout: 10000 },
    );

    // Capture initial data-run.
    const initialRun = await page.locator(".slide").first().getAttribute("data-run");

    // Wait for data-run to change (proves slideDone was called).
    await page.waitForFunction(
      (oldRun) => {
        const slide = document.querySelector(".slide");
        return slide && slide.dataset.run !== oldRun;
      },
      initialRun,
      { timeout: 10000 },
    );

    // Second slide should now be visible (use .first() because TransitionGroup
    // briefly keeps both old and new slides in the DOM during the transition).
    await expect(page.locator(".template-image-text h1").first()).toHaveText(
      "Slide 2 Title",
    );
  });

  test("Progress never stops - slides cycle through all playlists and wrap", async ({
    page,
  }) => {
    await setupClientRoutes(page);
    await page.goto("/client");

    // Expected slide order: Playlist 1 (Slide 1, Slide 2), Playlist 2 (Slide 3), then wrap.
    const expectedTitles = [
      "Slide 1 Title",
      "Slide 2 Title",
      "Slide 3 Title",
      "Slide 1 Title", // Wrap — proves progress never stops.
    ];

    for (const expectedTitle of expectedTitles) {
      // Use .first() because TransitionGroup briefly keeps both old and new
      // slides in the DOM during the CSS transition.
      await expect(page.locator(".template-image-text h1").first()).toHaveText(
        expectedTitle,
        { timeout: 10000 },
      );

      // Wait for this slide to finish (data-run changes).
      if (expectedTitle !== expectedTitles[expectedTitles.length - 1]) {
        const currentRun = await page
          .locator(".slide")
          .first()
          .getAttribute("data-run");
        await page.waitForFunction(
          (oldRun) => {
            const slide = document.querySelector(".slide");
            return slide && slide.dataset.run !== oldRun;
          },
          currentRun,
          { timeout: 10000 },
        );
      }
    }
  });
});
