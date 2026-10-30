import { test, expect } from "@playwright/test";

test.describe("image-text-0: ui tests", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/image-text-0");
  });

  test("Should have correct background image and media-contain", async ({
    page,
  }) => {
    await expect(page.getByText("Overskriften er her")).toBeVisible();
    await expect(page.locator(".box")).toBeVisible();
    await expect(page.locator(".background-image")).toBeVisible();
    await expect(page.locator(".background-image")).toHaveCSS(
      "background-image",
      new RegExp("fixtures/template/images/mountain1.jpeg"),
    );
    await expect(page.locator(".background-image")).toHaveCSS(
      "background-size",
      "contain",
    );
  });

  test("Should have animated-header set", async ({ page }) => {
    await expect(page.locator(".animated-header")).toBeVisible();
  });

  test("Should have half-size set", async ({ page }) => {
    await expect(page.locator(".half-size")).toBeVisible();
  });

  test("Should not have box-margin set", async ({ page }) => {
    await expect(page.locator(".box-margin")).not.toBeVisible();
  });

  test("Should have font size set", async ({ page }) => {
    await expect(page.locator(".font-size-xl")).toBeVisible();
  });

  test("Should have column set", async ({ page }) => {
    await expect(page.locator(".column")).toBeVisible();
  });
});

test.describe("image-text-1", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/image-text-1");
  });

  test("Should not have column set", async ({ page }) => {
    await expect(page.locator(".column")).not.toBeVisible();
  });

  test("Should have box-margin set", async ({ page }) => {
    await expect(page.locator(".box-margin")).toBeVisible();
  });

  test("Should have shadow set", async ({ page }) => {
    await expect(page.locator(".shadow")).toBeVisible();
  });

  test("Should have font size set", async ({ page }) => {
    await expect(page.locator(".font-size-sm")).toBeVisible();
  });

  test("Should not have media-contain", async ({ page }) => {
    await expect(page.locator(".background-image")).toHaveCSS(
      "background-size",
      "cover",
    );
  });
});

test.describe("image-text-reversed", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/image-text-reversed");
  });

  test("Should have reversed set", async ({ page }) => {
    await expect(page.locator(".reversed")).toBeVisible();
  });

  test("Should not have animated-header set", async ({ page }) => {
    await expect(page.locator(".animated-header")).not.toBeVisible();
  });

  test("Should not have half-size set", async ({ page }) => {
    await expect(page.locator(".half-size")).not.toBeVisible();
  });

  test("Should have box-margin set (due to being reversed)", async ({
    page,
  }) => {
    await expect(page.locator(".box-margin")).toBeVisible();
  });
});

test.describe("image-text-1-multiple-images", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/image-text-1-multiple-images");
  });

  test("Should flip through multiple images", async ({ page }) => {
    const slideChangeInterval = 6000;
    const imagePaths = [
      "/fixtures/template/images/mountain1.jpeg",
      "/fixtures/template/images/mountain2.jpeg",
      "/fixtures/template/images/mountain3.jpeg",
    ];

    const getBackground = () => page.locator(".background-image");

    for (const expectedImage of imagePaths) {
      const activeBackground = await getBackground();

      await expect(activeBackground).toHaveCSS(
        "background-image",
        new RegExp(expectedImage),
      );

      if (expectedImage !== imagePaths[imagePaths.length - 1]) {
        await page.waitForTimeout(slideChangeInterval);
      }
    }
  });
});
