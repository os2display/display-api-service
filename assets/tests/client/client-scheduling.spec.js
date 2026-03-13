import { test, expect } from "@playwright/test";
import {
  playlistJson,
  expiredSlideJson,
  futureSlideJson,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  mockScreenLogin,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client content scheduling", () => {
  test.beforeEach(async ({ page }) => {
    await clientBeforeEachTest(page);
  });

  test("Published slides display", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    // Screen renders with published content.
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });

    // Region should have received slides and render a slide element.
    await expect(page.locator(".region")).toBeVisible();
    await expect(page.locator(".slide")).toBeVisible({ timeout: 10000 });
  });

  test("Expired slides hidden", async ({ page }) => {
    // Create playlist with only expired slides.
    const expiredPlaylistSlidesJson = {
      "@context": "/contexts/PlaylistSlide",
      "@type": "hydra:Collection",
      "hydra:totalItems": 1,
      "hydra:member": [{ slide: expiredSlideJson }],
    };

    await mockScreenLogin(page, {
      playlistSlides: expiredPlaylistSlidesJson,
    });

    await gotoClient(page);

    // Screen renders but no slide should be visible since the slide is expired.
    // The schedule service filters out expired slides → contentEmpty event.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  test("Future slides hidden", async ({ page }) => {
    // Create playlist with only future slides.
    const futurePlaylistSlidesJson = {
      "@context": "/contexts/PlaylistSlide",
      "@type": "hydra:Collection",
      "hydra:totalItems": 1,
      "hydra:member": [{ slide: futureSlideJson }],
    };

    await mockScreenLogin(page, {
      playlistSlides: futurePlaylistSlidesJson,
    });

    await gotoClient(page);

    // Future slides are filtered out → contentEmpty → fallback shown.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  test("Content empty triggers fallback", async ({ page }) => {
    // Create an empty playlist (no slides at all).
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

    // No content → fallback is shown.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  test("Expired playlist is not shown", async ({ page }) => {
    // Create a playlist with expired publication dates.
    const expiredPlaylist = {
      ...playlistJson,
      published: {
        from: "2020-01-01T00:00:00+00:00",
        to: "2020-06-01T00:00:00+00:00",
      },
    };

    const expiredRegionPlaylistsJson = {
      "@context": "/contexts/PlaylistScreenRegion",
      "@type": "hydra:Collection",
      "hydra:totalItems": 1,
      "hydra:member": [{ playlist: expiredPlaylist }],
    };

    await mockScreenLogin(page, {
      regionPlaylists: expiredRegionPlaylistsJson,
    });

    await gotoClient(page);

    // Expired playlist is filtered by schedule service → fallback.
    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });
});
