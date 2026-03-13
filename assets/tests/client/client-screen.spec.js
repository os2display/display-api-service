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

  // Verifies that the Screen component renders with the correct @id attribute.
  // The screen fixture's @id is set as the DOM element's id by screen.jsx.
  test("Renders screen component from screen data", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    await expect(page.locator(".screen")).toHaveAttribute(
      "id",
      screenDataJson["@id"],
    );
  });

  // Verifies that a region element is rendered with the ULID extracted from
  // the layout region's @id path. idFromPath() extracts the 26-char ULID.
  test("Renders region in correct grid area", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    await expect(page.locator(".region")).toHaveCount(1);
    await expect(page.locator(".region")).toHaveAttribute(
      "id",
      "REGION01234567890123456789",
    );
  });

  // Verifies that screen.jsx applies CSS grid display based on the layout's
  // grid definition (rows/columns). The computed style should be "grid".
  test("Renders grid layout with correct CSS grid", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    const display = await page
      .locator(".screen")
      .evaluate((el) => getComputedStyle(el).display);
    expect(display).toBe("grid");
  });

  // Verifies that a layout with 2 regions renders both region elements.
  // Mock: multi-region screen/layout fixtures + custom route handler that
  // returns different playlists per region (registered AFTER mockScreenLogin
  // so it wins via LIFO route matching).
  test("Handles multi-region layout", async ({ page }) => {
    await mockScreenLogin(page, {
      screen: multiRegionScreenDataJson,
      layout: multiRegionLayoutDataJson,
    });

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

    // LIFO override: dispatch different playlists based on region ID in URL.
    await page.route("**/v2/screens/*/regions/*/playlists*", async (route) => {
      const url = route.request().url();
      if (url.includes("REGION02234567890123456789")) {
        await route.fulfill({ json: regionPlaylists2Json });
      } else {
        await route.fulfill({ json: regionPlaylistsJson });
      }
    });

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
    await expect(page.locator(".region")).toHaveCount(2);
  });
});
