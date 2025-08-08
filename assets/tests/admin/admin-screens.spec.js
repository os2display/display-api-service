import { test, expect } from "@playwright/test";
import { adminConfigJson, screenGroupsListJson, screensListJson } from "./data-fixtures.js";
import { beforeEachTest, fulfillDataRoute, fulfillEmptyRoutes, loginTest } from "./test-helper.js";

test.describe("Screen", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest({ page });

    await fulfillDataRoute(page, "**/screens*", screensListJson);

    await fulfillEmptyRoutes(page, ["**/campaigns*", "**/screen-groups*", "**/layouts*"]);

    await page.getByRole("link", { name: "Skærme", exact: true }).click();
    await expect(page.getByRole("heading", { name: "Skærme", exact: true })).toBeVisible();
  });

  test("Loads list", async ({ page }) => {
    await expect(page.locator("table").locator("tbody")).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td")).toHaveCount(16);
    await expect(page.locator("thead").locator("th")).toHaveCount(8);
  });

  test("It loads create screen page", async ({ page }) => {
    await page.getByLabel("Tilføj ny skærm").first().click();
    await expect(page.getByText("Opret ny skærm")).toBeVisible();
    await expect(page.locator("#save_screen")).toBeVisible();
  });

  test("It cancels create screen", async ({ page }) => {
    await page.getByLabel("Tilføj ny skærm").first().click();
    await expect(page.getByText("Opret ny skærm")).toBeVisible();
    await expect(page.locator("#cancel_screen")).toBeVisible();
    await page.locator("#cancel_screen").click();
    await expect(page.locator("#cancel_screen")).not.toBeVisible();
  });
});
