import { test, expect } from "@playwright/test";
import {
  errorJson,
  feedSourcesJson,
  feedSourcesJson2,
  feedSourcesJson3,
} from "./data-fixtures.js";
import { loginTest } from "./admin-helper.js";

test.describe("feed sources", () => {
  test.beforeEach(async ({ page }) => {
    await loginTest({ page });

    await page.route("**/feed-sources*", async (route) => {
      await route.fulfill({ json: feedSourcesJson });
    });

    await page.locator(".sidebar-nav .nav-link").getByText("Datakilder").click();
    await expect(page.locator("h1").getByText("Datakilder")).toBeVisible();
  });

  test("It loads create datakilde page", async ({ page }) => {
    page.getByText("Opret ny datakilde").click();
    await expect(page.locator("#save")).toBeVisible();
  });

  test("It display error toast on save error", async ({ page }) => {
    await page.route("**/feed-sources", async (route) => {
      await route.fulfill({ status: 500, json: errorJson });
    });
    page.getByText("Opret ny datakilde").click();

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).not.toBeVisible();
    await page.locator("#save").click();
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
    await expect(page).toHaveURL(/feed-sources\/create/);
  });

  test("Cancel create datakilde", async ({ page }) => {
    page.getByText("Opret ny datakilde").click();
    await expect(page.locator("#cancel")).toBeVisible();
    await page.locator("#cancel").click();
    await expect(page.locator("#cancel")).not.toBeVisible();
  });

  test("It loads datakilde list", async ({ page }) => {
    await expect(page.locator("table").locator("tbody")).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("It goes to edit", async ({ page }) => {
    await expect(page.locator("#feed-sourceTitle")).not.toBeVisible();

    await page.route("**/feed-sources*", async (route) => {
      await route.fulfill({ json: feedSourcesJson2 });
    });

    await page.route("**/feed-sources/*", async (route) => {
      await route.fulfill({ json: feedSourcesJson3 });
    });

    await page.locator("tbody").locator("tr td a").first().click();
    await expect(page.locator("#feed-sourceTitle")).toBeVisible();
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
    await expect(page.locator("thead").locator("th")).toHaveCount(6);
  });
});
