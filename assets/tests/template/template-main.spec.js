import { test, expect } from "@playwright/test";

test("Test template index", async ({ page }) => {
  await page.goto("/template");

  await expect(page).toHaveTitle(/OS2Display Templates/);
  await expect(page.getByText("Examples")).toBeVisible();
});

test.describe("Template Links", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template");
  });

  test("Should display all templates with links", async ({ page }) => {
    const templateIds = [
      "book-review-0",
      "calendar-0-multiple-days",
      "calendar-1-multiple",
      "calendar-2-single",
      "calendar-3-multiple-days",
      "calendar-4-single-booking",
      "contacts-underlined",
      "contacts-not-underlined",
      "iframe-0",
      "image-text-0",
      "image-text-1",
      "image-text-1-multiple-images",
      "image-text-2-logo",
      "image-text-3-font-sizes",
      "image-text-reversed",
      "image-text-4-test-theme",
      "instagram-0",
      "instagram-1-no-max-entries",
      "news-feed-0",
      "poster-0-single",
      "poster-0-single-override",
      "poster-1-subscription",
      "poster-2-single-no-feed",
      "rss-0-no-feed-progress",
      "rss-1-with-progress",
      "slideshow-0",
      "slideshow-1-no-stuff",
      "slideshow-2",
      "table-0",
      "table-1",
      "travel-multiple-stations",
      "travel-spacious-info-box",
      "travel-one-station",
      "video-0",
      "vimeo-player-0",
    ];

    for (const id of templateIds) {
      const link = page.locator(`#${id} a`);
      await expect(link).toBeVisible();
      await expect(link).toHaveAttribute("href", `/template/${id}`);
    }
  });

  test("Should display all screen templates with links", async ({ page }) => {
    const screenTemplateIds = [
      "two-split",
      "two-split-vertical",
      "two-split-vertical-reversed",
      "touch-template",
      "three-split",
      "three-split-horizontal",
      "six-areas",
      "full-screen",
      "four-areas",
    ];

    for (const id of screenTemplateIds) {
      const link = page.locator(`#${id} a`);
      await expect(link).toBeVisible();
      await expect(link).toHaveAttribute("href", `/template/${id}`);
    }
  });

  test("Should have working navigation for one of the links", async ({
    page,
  }) => {
    await page.click("#book-review-0 a");
    await expect(page).toHaveURL(/\/template\/book-review-0$/);
  });
});
