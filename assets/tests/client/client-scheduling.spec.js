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

  // Verifies that slides within their publication window are rendered.
  // The default slide fixture has from=2020, to=2099 → published now.
  test("Published slides display", async ({ page }) => {
    await mockScreenLogin(page);

    await gotoClient(page);

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
    await expect(page.locator(".region")).toBeVisible();
    await expect(page.locator(".slide")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that slides past their "to" date are filtered out.
  // The expired slide has to=2020 → isPublished() returns false →
  // schedule-service dispatches contentEmpty → fallback shown.
  test("Expired slides hidden", async ({ page }) => {
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

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that slides before their "from" date are filtered out.
  // The future slide has from=2099 → not yet published → contentEmpty.
  test("Future slides hidden", async ({ page }) => {
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

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that an empty playlist (zero slides) triggers the fallback.
  test("Content empty triggers fallback", async ({ page }) => {
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

  // Verifies that a playlist whose publication dates have passed is excluded.
  // Differs from expired slides: here the *playlist* itself is expired (to=2020),
  // so schedule-service skips it entirely regardless of its slides.
  test("Expired playlist is not shown", async ({ page }) => {
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

    await expect(page.locator(".fallback")).toBeVisible({ timeout: 10000 });
  });
});
