import { test, expect } from "@playwright/test";
import { awaitDataRoute, awaitEmptyRoutes, beforeEachTest, loginTest } from "./test-helper.js";
import { slidesJson1 } from "./data-fixtures.js";

test.describe("Campaign pages work", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest({ page });

    await awaitEmptyRoutes(page, ["**/playlists*", "**/screens*", "**/screen-groups*"]);

    await page.locator(".sidebar-nav .nav-link").getByText("Kampagner").click();
    await expect(page.locator("h1").getByText("Kampagner")).toBeVisible();
    await page.getByText("Opret ny kampagne").click();
  });

  test("It cancels create campaign", async ({ page }) => {
    await expect(page.locator("#cancel_playlist")).toBeVisible();
    await page.locator("#cancel_playlist").click();
    await expect(page.locator("#cancel_playlist")).not.toBeVisible();
  });

  test("It removes slide", async ({ page }) => {
    // Intercept slides in dropdown
    await awaitDataRoute(page, "**/slides*", slidesJson1);

    // Pick slide
    await page
      .locator("#slides-section")
      .locator(".dropdown-container")
      .nth(0)
      .press("Enter");
    await page
      .locator("#slides-section")
      .locator(".search")
      .locator('[type="text"]')
      .fill("d");
    await page
      .locator("#slides-section")
      .locator('[type="checkbox"]')
      .nth(1)
      .check();
    await page
      .locator("#slides-section")
      .locator(".dropdown-container")
      .nth(0)
      .click();
    await expect(
      page.locator("#slides-section").locator("tbody").locator("tr td"),
    ).toHaveCount(6);

    // Remove slide
    await page.locator(".remove-from-list").click();

    // See that slides section is removed.
    await expect(page.getByText("Afspilningsrækkefølge")).not.toBeVisible();
  });
});
