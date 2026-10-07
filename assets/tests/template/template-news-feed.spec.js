import { test, expect } from "@playwright/test";

test.describe("news-feed-0: ui tests", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/news-feed-0");
  });

  test("Should render template with media", async ({ page }) => {
    const slide = page.locator(".slide");
    await expect(slide).toBeVisible();

    const backgroundImage = slide.locator(".media-section");
    await expect(backgroundImage).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/sunset-full-hd.jpg"),
    );
  });

  test("Should render template with title", async ({ page }) => {
    const slide = page.locator(".slide");

    const title = slide.locator(".title");
    await expect(title).toHaveText(
      "Aenean scelerisque ligula ante, sed tristique tellus?",
    );
  });

  test("Should render template with author", async ({ page }) => {
    const slide = page.locator(".slide");

    const authorAndDate = slide.locator(".author");
    await expect(authorAndDate).toHaveText("18. nov. 2024 ▪ Test Testesen");
  });

  test("Should render template with description", async ({ page }) => {
    const slide = page.locator(".slide");
    const description = slide.locator(".description");
    await expect(description).toContainText("Duis volutpat orci lectus");
  });

  test("Should render template with qr code", async ({ page }) => {
    const slide = page.locator(".slide");

    const qrCode = slide.locator("img.qr");
    await expect(qrCode).toBeVisible();
    await expect(qrCode).toHaveAttribute("src", /data:image\/png;base64/);
  });

  test("Should render template with read more link", async ({ page }) => {
    const slide = page.locator(".slide");

    const readMore = slide.locator(".read-more");
    await expect(readMore).toHaveText("Læs hele nyheden");

    const link = slide.locator(".link");
    await expect(link).toHaveText("https://example.com/news/1");
  });

  test("Should set media contain", async ({ page }) => {
    const mediaContain = page.locator(".media-contain");
    await expect(mediaContain).toHaveCount(1);
  });
  test("Should render template with media (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");
    await expect(slide).toBeVisible();

    const backgroundImage = slide.locator(".media-section");
    await expect(backgroundImage).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain4.jpeg"),
    );
  });

  test("Should render template with title (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");

    const title = slide.locator(".title");
    await expect(title).toHaveText("Duis volutpat orci lectus.");
  });

  test("Should render template with author (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");

    const authorAndDate = slide.locator(".author");
    await expect(authorAndDate).toHaveText(
      "18. nov. 2024 ▪ Aenean Scelerisque",
    );
  });

  test("Should render template with description (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");
    const description = slide.locator(".description");
    await expect(description).toContainText("Summary2");
  });

  test("Should render template with qr code (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");

    const qrCode = slide.locator("img.qr");
    await expect(qrCode).toBeVisible();
    await expect(qrCode).toHaveAttribute("src", /data:image\/png;base64/);
  });

  test("Should render template with read more link (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");

    const readMore = slide.locator(".read-more");
    await expect(readMore).toHaveText("Læs hele nyheden");

    const link = slide.locator(".link");
    await expect(link).toHaveText("https://example.com/news/2");
  });

  test("Should set media contain (after 2100 seconds", async ({ page }) => {
    await page.waitForTimeout(2100);
    const mediaContain = page.locator(".media-contain");
    await expect(mediaContain).toHaveCount(1);
  });

  test("Should use fallbackImage (after 4100 seconds", async ({ page }) => {
    await page.waitForTimeout(4100);
    const slide = page.locator(".slide");
    await expect(slide).toBeVisible();

    const backgroundImage = slide.locator(".media-section");
    await expect(backgroundImage).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });
});

test.describe("news-feed-no-media-contain: ui tests", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/news-feed-no-media-contain");
  });

  test("Should not set media contain", async ({ page }) => {
    const mediaContain = page.locator(".media-contain");
    await expect(mediaContain).toHaveCount(0);
  });

  test("Should render template with alternative read more link (after 2100 seconds", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);
    const slide = page.locator(".slide");

    const readMore = slide.locator(".read-more");
    await expect(readMore).toHaveText("Read more text");

    const link = slide.locator(".link");
    await expect(link).toHaveText("https://example.com/news/3");
  });
});
