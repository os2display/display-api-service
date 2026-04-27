import { test, expect } from "@playwright/test";

test.describe("table-0: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/table-0");
  });

  test("Component should be visible", async ({ page }) => {
    const templateTable = page.locator(".template-table");
    await expect(templateTable).toBeVisible();
  });

  test("Should have correct background image", async ({ page }) => {
    const backgroundImage = page.locator(".template-table");
    await expect(backgroundImage).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should contain header with title", async ({ page }) => {
    const title = page.locator(".template-table-header .title");
    await expect(title).toHaveText("Overskrift");
  });

  test("Should have correct font size", async ({ page }) => {
    const fontSize = page.locator(".font-size-lg");
    await expect(fontSize).toBeVisible();
    const fontSizeS = page.locator(".font-size-s");
    await expect(fontSizeS).toHaveCount(0);
  });

  test("Should set media contain", async ({ page }) => {
    const mediaContain = page.locator(".media-contain");
    await expect(mediaContain).toHaveCount(0);
  });

  test("Should have two column headers", async ({ page }) => {
    const columnHeaders = page.locator(".column-header");
    await expect(columnHeaders).toHaveCount(2);
    await expect(columnHeaders.nth(0)).toHaveText("Kolonne 1");
    await expect(columnHeaders.nth(1)).toHaveText("Kolonne 2");
  });

  test("Separator exists (default is true)", async ({ page }) => {
    const separator = page.locator(".separator");
    await expect(separator).toBeVisible();
  });

  test("Should render table cell content correctly", async ({ page }) => {
    const columns = page.locator(".column");
    await expect(columns).toHaveCount(4);

    const columnTexts = [
      "Række 1",
      "Række 1, celle 2",
      "Række 2",
      "Række 2, celle 2",
    ];

    for (let i = 0; i < columnTexts.length; i++) {
      await expect(columns.nth(i)).toHaveText(columnTexts[i]);
    }
  });

  test("Should contain description text in main in the bottom", async ({
    page,
  }) => {
    const bottomText = page.locator(".bottom-text");
    await expect(bottomText).toContainText("Bread text");
    const topText = page.locator(".top-text");
    await expect(topText).toHaveCount(0);
  });
});

test.describe("table-1: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/table-1");
  });

  test("Component should be visible", async ({ page }) => {
    const templateTable = page.locator(".template-table");
    await expect(templateTable).toBeVisible();
  });

  test("Should have correct background image", async ({ page }) => {
    const backgroundImage = page.locator(".template-table");
    await expect(backgroundImage).not.toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should have correct font size", async ({ page }) => {
    const fontSize = page.locator(".font-size-s");
    await expect(fontSize).toBeVisible();
    const fontSizeLG = page.locator(".font-size-lg");
    await expect(fontSizeLG).toHaveCount(0);
  });

  test("Should set media contain", async ({ page }) => {
    const mediaContain = page.locator(".media-contain");
    await expect(mediaContain).toBeVisible();
  });

  test("Should contain header with title", async ({ page }) => {
    const title = page.locator(".template-table-header .title");
    await expect(title).toHaveText("Overskrift2");
  });

  test("Should have three column headers", async ({ page }) => {
    const columnHeaders = page.locator(".column-header");
    await expect(columnHeaders).toHaveCount(3);
    await expect(columnHeaders.nth(0)).toHaveText("Kolonne 1");
    await expect(columnHeaders.nth(1)).toHaveText("Kolonne 2");
    await expect(columnHeaders.nth(2)).toHaveText("Kolonne 3");
  });

  test("Separator does not exist", async ({ page }) => {
    const separator = page.locator(".separator");
    await expect(separator).toHaveCount(0);
  });

  test("Should render table cell content correctly", async ({ page }) => {
    const columns = page.locator(".column");
    await expect(columns).toHaveCount(9);

    const columnTexts = [
      "Række 1, kolonne 1",
      "Række 1, kolonne 2",
      "Række 1, kolonne 3",
      "Række 2, kolonne 1",
      "Række 2, kolonne 2",
      "Række 2, kolonne 3",
      "Række 3, kolonne 1",
      "Række 3, kolonne 2",
      "Række 3, kolonne 3",
    ];

    for (let i = 0; i < columnTexts.length; i++) {
      await expect(columns.nth(i)).toHaveText(columnTexts[i]);
    }
  });

  test("Should contain description text in main in the top", async ({
    page,
  }) => {
    const topText = page.locator(".top-text");
    await expect(topText).toContainText("Bread text");
    const bottomText = page.locator(".bottom-text");
    await expect(bottomText).toHaveCount(0);
  });
});
