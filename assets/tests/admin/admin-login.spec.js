import { test, expect } from "@playwright/test";
import {
  adminConfigJson,
  tokenAdminJson,
  tokenEditorJson,
  tokenTenantsJson,
} from "./data-fixtures.js";
import { fulfillDataRoute, fulfillEmptyRoutes } from "./test-helper.js";

test.describe("Login works", () => {
  test.beforeEach(async ({ page }) => {
    await page.route("**/config/admin", async (route) => {
      await route.fulfill({ json: adminConfigJson });
    });
  });

  test("Login one tenant works", async ({ page }) => {
    await page.goto("/admin/playlist/list");

    await fulfillEmptyRoutes(page, ["**/playlists*"]);

    await fulfillDataRoute(page, "**/token", tokenAdminJson);

    await page.locator("#login").click();
    await expect(page.locator(".name")).toHaveText("John Doe");
  });

  test("Login three tenant works", async ({ page }) => {
    await page.goto("/admin/playlist/list");

    await fulfillEmptyRoutes(page, ["**/playlists*"]);

    await fulfillDataRoute(page, "**/token", tokenTenantsJson);

    await page.locator("#login").click();

    // Expect dropdown with tenants
    await expect(page.locator(".dropdown-container")).toBeVisible();
  });

  test("Login with tenant that has role editor", async ({ page }) => {
    await page.goto("/admin/playlist/list");

    await fulfillEmptyRoutes(page, ["**/playlists*"]);

    await fulfillDataRoute(page, "**/token", tokenEditorJson);

    await page.locator("#login").click();

    await expect(page.locator(".name")).toHaveText("John Doe");
    await expect(page.locator(".sidebar-nav").locator(".nav-item")).toHaveCount(
      4,
    );
  });

  test("Role editor should not be able to visit restricted route", async ({
    page,
  }) => {
    await page.goto("/admin/shared/list");

    await fulfillDataRoute(page, "**/token", tokenEditorJson);

    await page.locator("#login").click();

    await expect(page.locator("main").locator("div")).toHaveText(
      "Du har ikke adgang til denne side",
    );
  });
});
