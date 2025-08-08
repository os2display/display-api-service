import { test, expect } from "@playwright/test";
import { beforeEachTest, fulfillDataRoute, fulfillEmptyRoutes, loginTest } from "./test-helper.js";
import { emptyJson, errorJson, playlistListJson, onSaveJson, playlistSingleJson } from "./data-fixtures.js";

test.describe("Playlist create tests", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest({ page });

    await fulfillEmptyRoutes(page, ["**/tenants*"]);

    await page.getByLabel("Tilføj ny spilleliste").first().click();
    await expect(page.getByText("Opret ny spilleliste:")).toBeVisible();
  });

  test("It loads create playlist page", async ({ page }) => {
    await expect(page.locator("#save_playlist")).toBeVisible();
  });

  test("It redirects on save", async ({ page }) => {
    await fulfillDataRoute(page, "**/playlists", onSaveJson);

    // Displays success toast and redirects
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--success")
    ).not.toBeVisible();
    await page.locator("#save_slide_and_close").click();
    await expect(
      page
        .locator(".Toastify")
        .locator(".Toastify__toast--success")
        .getByText(/gemt/)
        .first()
    ).toBeVisible();
    await expect(page).toHaveURL(/playlist\/list/);
  });

  test("It display error toast on save error", async ({ page }) => {
    await fulfillDataRoute(page, "**/playlists", errorJson, 500);

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error")
    ).not.toBeVisible();
    await page.locator("#save_playlist").click();
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error")
    ).toBeVisible();
    await expect(
      page
        .locator(".Toastify")
        .locator(".Toastify__toast--error")
        .getByText(/An error occurred/)
        .first()
    ).toBeVisible();
    await expect(page).toHaveURL(/playlist\/create/);
  });

  test("It cancels create playlist", async ({ page }) => {
    await expect(page.locator("#cancel_playlist")).toBeVisible();
    await page.locator("#cancel_playlist").click();
    await expect(page.locator("#cancel_playlist")).not.toBeVisible();
  });
});

test.describe("Playlist list tests", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest({ page });

    await fulfillDataRoute(page, "**/playlists*", playlistListJson);

    await fulfillEmptyRoutes(page, ["**/tenants*"]);

    await page.getByRole("link", { name: "Spillelister", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Spillelister", exact: true })).toBeVisible();
  });

  test("It loads playlist list", async ({ page }) => {
    await expect(
      page.locator("table").locator("tbody").first()
    ).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("It goes to edit", async ({ page }) => {
    await fulfillDataRoute(page, "**/playlists/*", playlistSingleJson);

    await expect(page.locator("#playlistTitle")).not.toBeVisible();
    await page.locator("tbody").locator("tr td a").nth(0).click();
    await expect(page.locator("#playlistTitle")).toBeVisible();
  });

  test("The correct amount of column headers loaded (playlist list)",
    async ({ page }) => {
      await expect(page.locator("thead").locator("th")).toHaveCount(8);
    });
});
