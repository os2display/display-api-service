import { test, expect } from "@playwright/test";
import {
  screenDataJson,
  multiRegionScreenDataJson,
  multiRegionLayoutDataJson,
  regionPlaylistsJson,
  playlistJson,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  mockScreenLogin,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client screen rendering", () => {
  test.beforeEach(async ({ page }) => {
    await clientBeforeEachTest(page);
  });

  test("Renders screen component from screen data", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    // Screen component should render.
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Screen should have the correct id attribute.
    await expect(page.locator(".screen")).toHaveAttribute(
      "id",
      screenDataJson["@id"],
    );
  });

  test("Renders region in correct grid area", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // One region should be rendered.
    await expect(page.locator(".region")).toHaveCount(1);

    // Region should have the correct id (26-char ULID from the layout region @id).
    await expect(page.locator(".region")).toHaveAttribute(
      "id",
      "REGION01234567890123456789",
    );
  });

  test("Renders grid layout with correct CSS grid", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Check that the screen element has grid display.
    const display = await page
      .locator(".screen")
      .evaluate((el) => getComputedStyle(el).display);
    expect(display).toBe("grid");
  });

  test("Handles multi-region layout", async ({ page }) => {
    // Set up login with multi-region screen and layout.
    await mockScreenLogin(page, {
      screen: multiRegionScreenDataJson,
      layout: multiRegionLayoutDataJson,
    });

    // Create a second playlist for the second region.
    const playlist2Json = {
      ...playlistJson,
      "@id": "/v2/playlists/PLAYLT02234567890123456789",
      title: "Test Playlist 2",
      slides: "/v2/playlists/PLAYLT02234567890123456789/slides",
    };

    const regionPlaylists2Json = {
      "@context": "/contexts/PlaylistScreenRegion",
      "@type": "hydra:Collection",
      "hydra:totalItems": 1,
      "hydra:member": [{ playlist: playlist2Json }],
    };

    // Override the region playlists route (registered AFTER mockScreenLogin,
    // so this handler takes priority due to LIFO route matching).
    await page.route(
      "**/v2/screens/*/regions/*/playlists*",
      async (route) => {
        const url = route.request().url();
        if (url.includes("REGION02234567890123456789")) {
          await route.fulfill({ json: regionPlaylists2Json });
        } else {
          await route.fulfill({ json: regionPlaylistsJson });
        }
      },
    );

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Both regions should render.
    await expect(page.locator(".region")).toHaveCount(2);
  });
});
