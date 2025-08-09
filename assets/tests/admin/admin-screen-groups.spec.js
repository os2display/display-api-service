import { test, expect } from "@playwright/test";
import { beforeEachTest, fulfillDataRoute, fulfillEmptyRoutes, loginTest } from "./test-helper.js";
import {
  playlistListJson,
  onSaveJson,
  errorJson,
  screenGroupsListJson,
  screenGroupsSingleJson
} from "./data-fixtures.js";
import { json } from "react-router-dom";

test.describe("Create group page works", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillEmptyRoutes(page, ["**/screen-groups*"]);

    await page.getByRole("link", { name: "Grupper", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Grupper", exact: true })).toBeVisible();
    await page.getByRole("button", { name: "Opret ny gruppe", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Opret ny gruppe", exact: true })).toBeVisible();
  });

  test("It loads create group page", async ({ page }) => {
    await expect(page.locator("#save_group")).toBeVisible();
  });

  test("It redirects on save", async ({ page }) => {
    await fulfillDataRoute(page, "**/screen-groups", onSaveJson);

    // Displays success toast and redirects
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--success"),
    ).not.toBeVisible();
    await page.locator("#save_group").click();
    await expect(
      page
        .locator(".Toastify")
        .locator(".Toastify__toast--success")
        .getByText(/gemt/)
        .first(),
    ).toBeVisible();
    await expect(page).toHaveURL(/group\/list/);
  });

  test("It cancels create group", async ({ page }) => {
    await expect(page.locator("#cancel_group")).toBeVisible();
    await page.locator("#cancel_group").click();
    await expect(page.locator("#cancel_group")).not.toBeVisible();
  });

  test("It display error toast on save error", async ({ page }) => {
    await fulfillDataRoute(page, "**/screen-groups", errorJson, 500);

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).not.toBeVisible();
    await page.locator("#save_group").click();
    await expect(
      page.locator(".Toastify").getByText(/An error occurred/),
    ).toBeVisible();
    await expect(page).toHaveURL(/group\/create/);
  });
});

test.describe("Groups list works", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillEmptyRoutes(page, ['**/screens*']);
    await fulfillDataRoute(page, "**/screen-groups*", screenGroupsListJson);

    await page.getByRole("link", { name: "Grupper", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Grupper", exact: true })).toBeVisible();
  });

  test("It loads groups list", async ({ page }) => {
    await expect(page.locator("table").locator("tbody")).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("It goes to edit (groups list)", async ({ page }) => {
    await fulfillDataRoute(page, "**/screen-groups/000RAH746Q1AD8011Z1JNV06N3", screenGroupsSingleJson);

    await expect(page.locator("#groupTitle")).not.toBeVisible();
    await page.locator("tbody").locator("tr td a").nth(0).click();
    await expect(page.locator("#groupTitle")).toBeVisible();
  });

  test("It opens delete modal (groups list)", async ({ page }) => {
    await expect(page.locator("#delete-modal")).not.toBeVisible();
    await page.locator("tbody").locator("tr td button").nth(1).click();
    await expect(page.locator("#delete-modal")).toBeVisible();
  });

  test("The correct amount of column headers loaded (groups list)", async ({
    page,
  }) => {
    await expect(page.locator("thead").locator("th")).toHaveCount(5);
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
