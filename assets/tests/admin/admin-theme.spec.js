import { test, expect } from "@playwright/test";
import { beforeEachTest, fulfillDataRoute, fulfillEmptyRoutes, loginTest } from "./test-helper.js";
import { errorJson, feedSourcesJson2, screensListJson, themesJson, themesSingleJson } from "./data-fixtures.js";

test.describe("Theme", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/themes*", themesJson);
//    await fulfillEmptyRoutes(page, ["**/campaigns*", "**/screen-groups*", "**/layouts*"]);

    await page.getByRole("link", { name: "Temaer", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Temaer", exact: true })).toBeVisible();
  });

  test("It loads create theme page", async ({ page }) => {
    await page.getByText("Opret nyt tema").click();
    await expect(page.locator("#save_theme")).toBeVisible();
  });

  test("It display error toast on save error", async ({ page }) => {
    await fulfillDataRoute(page, "**/themes", errorJson, 500);
    await page.getByText("Opret nyt tema").click();

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).not.toBeVisible();
    await page.locator("#save_theme").click();
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(
      page
        .locator(".Toastify")
        .locator(".Toastify__toast--error")
        .getByText(/An error occurred/)
        .first(),
    ).toBeVisible();
    await expect(page).toHaveURL(/themes\/create/);
  });

  test("It cancels create theme", async ({ page }) => {
    await page.getByText("Opret nyt tema").click();
    await expect(page.locator("#cancel_theme")).toBeVisible();
    await page.locator("#cancel_theme").click();
    await expect(page.locator("#cancel_theme")).not.toBeVisible();
  });

  test("It loads themes list", async ({ page }) => {
    await expect(page.locator("table").locator("tbody")).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("It goes to edit", async ({ page }) => {
    await expect(page.locator("#themeTitle")).not.toBeVisible();

    await fulfillDataRoute(page, "**/themes/*", themesSingleJson);

    await page.locator("tbody").locator("tr td a").first().click();
    await expect(page.locator("#themeTitle")).toBeVisible();
  });

  test("It opens delete modal", async ({ page }) => {
    await expect(page.locator("#delete-modal")).not.toBeVisible();
    await page
      .locator("tbody")
      .nth(0)
      .locator(".remove-from-list")
      .nth(1)
      .click();
    await expect(page.locator("#delete-modal")).toBeVisible();
  });

  test("The correct amount of column headers loaded", async ({ page }) => {
    await expect(page.locator("thead").locator("th")).toHaveCount(5);
  });
});
