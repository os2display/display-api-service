import { test, expect } from "@playwright/test";
import { fulfillDataRoute, beforeEachTest, loginTest } from "./test-helper.js";
import { mediaListJson } from "./data-fixtures.js";

test.describe("media list tests", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/media*", mediaListJson);

    await page.locator(".sidebar-nav .nav-link").getByText("Medier").click();
  });

  test("It loads media list", async ({ page }) => {
    await expect(page.locator(".media-list")).not.toBeEmpty();
    await expect(page.locator(".media-item")).toHaveCount(2);
  });

  test("It selects images (media-list)", async ({ page }) => {
    await expect(page.locator("#delete_media_button")).toBeDisabled();
    await page.locator(".media-list").locator("input").nth(0).click();
    await expect(
      page.locator(".media-list").locator(".card").first(),
    ).toHaveClass(/selected/);
    await expect(page.locator("#delete_media_button")).not.toBeDisabled();
  });

  test("It opens delete modal (media-list)", async ({ page }) => {
    await expect(page.locator("#delete-modal")).not.toBeVisible();
    await page.locator(".media-list").locator("input").nth(0).click();
    await page.locator("#delete_media_button").click();
    await expect(page.locator("#delete-modal")).toBeVisible();
  });
});
