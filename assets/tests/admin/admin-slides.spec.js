import { test, expect } from "@playwright/test";
import {
  beforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  loginTest,
} from "./test-helper.js";
import {
  emptyJson,
  errorJson,
  slidesListJson,
  templatesListJson,
} from "./data-fixtures.js";

test.describe("Slide create", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/templates*", templatesListJson);
    await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);

    await page.getByLabel("Tilføj nyt slide").first().click();
    await expect(page.getByText("Opret nyt slide:")).toBeVisible();
  });

  test("It loads create slide page", async ({ page }) => {
    await expect(page.locator("#save_slide")).toBeVisible();
  });

  test("It display error toast on save error", async ({ page }) => {
    await fulfillDataRoute(page, "**/slides", errorJson, 500);

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).not.toBeVisible();
    await page.locator("#save_slide").click();
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error").first(),
    ).toBeVisible();
    await expect(page).toHaveURL(/slide\/create/);
  });

  test("It cancels create slide", async ({ page }) => {
    await expect(page.locator("#cancel_slide")).toBeVisible();
    await page.locator("#cancel_slide").click();
    await expect(page.locator("#cancel_slide")).not.toBeVisible();
  });
});

test.describe("Slides list", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/templates*", templatesListJson);
    await fulfillDataRoute(page, "**/templates/*", emptyJson);
    await fulfillDataRoute(page, "**/slides*", slidesListJson);
    await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);

    await page.getByLabel("Tilføj nyt slide").first().click();
    await expect(page.getByText("Opret nyt slide:")).toBeVisible();
    await page.getByRole("link", { name: "Slides" }).first().click();
    await expect(page.getByRole("heading", { name: "Slides" })).toBeVisible();
  });

  test("The correct amount of column headers loaded", async ({ page }) => {
    await expect(page.locator("thead").locator("th")).toHaveCount(9);
  });

  test("It removes all selected", async ({ page }) => {
    await page.locator("tbody").locator("tr td input").nth(0).click();
    await expect(
      page.locator("tbody").locator("tr").nth(0).getByRole("checkbox"),
    ).toBeChecked();
    await page.locator("#clear-rows-button").click();
    await expect(
      page.locator("tbody").locator("tr").nth(0).getByRole("checkbox"),
    ).not.toBeChecked();
  });
});
