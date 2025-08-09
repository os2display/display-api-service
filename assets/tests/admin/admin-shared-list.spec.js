import { test, expect } from "@playwright/test";
import { beforeEachTest, fulfillDataRoute, fulfillEmptyRoutes, loginTest } from "./test-helper.js";
import { playlistListJson, screensListJson } from "./data-fixtures.js";

test.describe("Shared list tests", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/playlists*", playlistListJson);

    await page.getByRole("link", { name: "Delte spillelister", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Delte spillelister", exact: true })).toBeVisible();
  });

  test("It loads shared playlist list", async ({ page }) => {
    await expect(
      page.locator("table").locator("tbody").first(),
    ).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("A shared playlist should not be editable or deletable", async ({
    page,
  }) => {
    await expect(page.locator("#delete-button")).not.toBeVisible();
    await expect(page.locator("#clear-rows-button")).toBeDisabled();
    await expect(page.locator("tbody").locator("tr td a")).not.toBeVisible();
    await expect(
      page.locator("tbody").locator("btn btn-danger"),
    ).not.toBeVisible();
  });

  test("The correct amount of column headers loaded (shared playlist list)", async ({
    page,
  }) => {
    await expect(page.locator("thead").locator("th")).toHaveCount(3);
  });
});
