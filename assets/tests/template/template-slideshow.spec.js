import { test, expect } from "@playwright/test";

test.describe("slideshow-0: UI tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/slideshow-0");
  });

  test("Slides/image change every 5 seconds", async ({ page }) => {
    const slideChangeInterval = 6000;
    const imagePaths = [
      "/fixtures/template/images/mountain1.jpeg",
      "/fixtures/template/images/mountain2.jpeg",
      "/fixtures/template/images/mountain3.jpeg",
      "/fixtures/template/images/mountain4.jpeg",
    ];

    const getActiveSlide = () =>
      page.locator('.fade-container[data-active="true"]');
    const getInactiveSlides = () =>
      page.locator('.fade-container[data-active="false"]');

    let previousIndex = null;

    for (const expectedImage of imagePaths) {
      const activeSlide = await getActiveSlide();
      const activeIndex = await activeSlide.getAttribute("data-index");

      if (previousIndex !== null) {
        expect(activeIndex).not.toBe(previousIndex);
      }
      previousIndex = activeIndex;

      await expect(activeSlide).toHaveCSS("opacity", "1");

      const image = activeSlide.locator(".image");
      await expect(image).toHaveCSS(
        "background-image",
        new RegExp(expectedImage),
      );

      const inactiveSlides = getInactiveSlides();
      const count = await inactiveSlides.count();
      for (let i = 0; i < count; i++) {
        await expect(inactiveSlides.nth(i)).toHaveCSS("opacity", "0");
      }

      if (expectedImage !== imagePaths[imagePaths.length - 1]) {
        await page.waitForTimeout(slideChangeInterval);
      }
    }
  });

  test("Should apply logo classes", async ({ page }) => {
    const img = page.locator("img");
    expect(img).toHaveAttribute(
      "class",
      "logo logo-margin l logo-position-bottom-right",
    );
  });
});

test.describe("slideshow-1-no-stuff: UI Tests", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/slideshow-1-no-stuff");
  });

  test("Should not show logo", async ({ page }) => {
    const img = page.locator("img");
    expect(img).toHaveCount(0);
  });
});

test.describe("slideshow-2: UI Tests", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/slideshow-2");
  });

  test("Should apply logo classes", async ({ page }) => {
    const img = page.locator("img");
    expect(img).toHaveAttribute("class", "logo l logo-position-top-right");
  });
});
