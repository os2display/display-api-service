import { test, expect } from "@playwright/test";

test.describe("poster-0-single-override: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/poster-0-single-override");
  });

  test("Should render poster with logo", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");
    await expect(poster).toBeVisible();

    const imageArea = poster.locator(".image-area.media-contain");
    await expect(imageArea).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should render poster with title (overridden)", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".header-area h1")).toHaveText(
      "Override title",
    );
  });

  test("Should render poster with sub-title (overridden)", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".header-area .lead")).toHaveText(
      "Override subtitle",
    );
  });

  test("Should render feed data", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .place")).toHaveText(
      "Lorem ipsum, Aarhus C",
    );
  });

  test("Should render overridden ticket price", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .ticket")).toHaveText(
      "Override ticket price",
    );
  });

  test("Should render overridden read more text", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .moreinfo")).toHaveText(
      "Læs mere her: www.example.com",
    );
  });

  test("Should render overridden read more url", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .look-like-link")).toHaveText(
      "https://example.com/",
    );
  });

  test("Should not render date", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".date")).toHaveCount(0);
  });

  test("Should render logo", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    const logoImage = poster.locator(".logo-area img");
    await expect(logoImage).toHaveAttribute(
      "src",
      "/fixtures/template/images/mountain1.jpeg",
    );
  });
});

test.describe("poster-0-single: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/poster-0-single");
  });

  test("Should render poster with logo", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");
    await expect(poster).toBeVisible();

    const imageArea = poster.locator(".image-area.media-contain");
    await expect(imageArea).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should render poster with title", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".header-area h1")).toHaveText("Lorem ipsum");
  });

  test("Should render poster with sub-title", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".header-area .lead")).toHaveText(
      "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua....",
    );
  });

  test("Should render feed data", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .place")).toHaveText(
      "Lorem ipsum, Aarhus C",
    );
  });

  test("Should render ticket price", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .ticket")).toHaveText("75-150 kr.");
  });

  test("Should render read more text", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .moreinfo")).not.toBeVisible();
  });

  test("Should render read more url", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".info-area .look-like-link")).toHaveText(
      "www.example.dk",
    );
  });

  test("Should render date", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    await expect(poster.locator(".date")).toHaveCount(2);
  });

  test("Should render logo", async ({ page }) => {
    const poster = page.locator(".template-poster.with-logo");

    const logoImage = poster.locator(".logo-area img");
    await expect(logoImage).toHaveAttribute(
      "src",
      "/fixtures/template/images/mountain1.jpeg",
    );
  });
});

test.describe("poster-1-subscription: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/poster-1-subscription");
  });

  test("Should render poster without logo", async ({ page }) => {
    const poster = page.locator(".template-poster");
    await expect(poster).toBeVisible();
  });

  test("Should render poster with title", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".header-area h1")).toHaveText("Lorem ipsum");
  });

  test("Should render poster with sub-title", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".header-area .lead")).toHaveText(
      "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua....",
    );
  });

  test("Should render feed data", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .place")).toHaveText(
      "Lorem ipsum, Aarhus C",
    );
  });

  test("Should render ticket price", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .ticket")).toHaveText("75-150 kr.");
  });

  test("Should render read more text", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .moreinfo")).toHaveText(
      "Læs mere her: www.example.com",
    );
  });

  test("Should render read more url", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .look-like-link")).toHaveText(
      "www.example.dk",
    );
  });

  test("Should render date", async ({ page }) => {
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".date")).toHaveCount(2);
  });

  test("Should not render logo", async ({ page }) => {
    const poster = page.locator(".template-poster");

    const logoImage = poster.locator(".logo-area img");
    await expect(logoImage).toHaveCount(0);
  });

  test("Should render poster without logo (after 2100 ms)", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");
    await expect(poster).toBeVisible();
  });

  test("Should render poster with title (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".header-area h1")).toHaveText("Ipsum lorem");
  });

  test("Should render poster with sub-title (after 2100 ms)", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".header-area .lead")).toHaveText(
      "Ipsum lorem dolor...",
    );
  });

  test("Should render feed data (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .place")).toHaveText(
      "Lorem ipsum, Aarhus C",
    );
  });

  test("Should render ticket price (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .ticket")).toHaveText(
      "En milliard kroner.",
    );
  });

  test("Should render read more text (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .moreinfo")).toHaveText(
      "Læs mere her: www.example.com",
    );
  });

  test("Should render read more url (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".info-area .look-like-link")).toHaveText(
      "www.example2.dk",
    );
  });

  test("Should render date (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    await expect(poster.locator(".date")).toHaveCount(2);
  });

  test("Should not render logo (after 2100 ms)", async ({ page }) => {
    await page.waitForTimeout(2100);
    const poster = page.locator(".template-poster");

    const logoImage = poster.locator(".logo-area img");
    await expect(logoImage).toHaveCount(0);
  });
});
