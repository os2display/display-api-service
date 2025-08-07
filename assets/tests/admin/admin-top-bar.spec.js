import { test, expect } from "@playwright/test";
import { abortUnhandledRoutes, loginTest } from "./admin-helper.js";
import { emptyJson } from "./data-fixtures.js";

test.describe("Nav items loads", () => {
  test.beforeEach(async ({ page }) => {
    await abortUnhandledRoutes(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest({ page });
  });

  test("It loads", async ({ page }) => {
    await expect(page.locator("nav")).toBeVisible();
  });

  test("It navigates to slides list", async ({ page }) => {
    await page.route("**/media*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    // Go to media page first, since Slide is the starting page, to allow for link click.
    await page.getByRole("link", { name: "Medier" }).click();

    await page.getByRole("link", { name: "Slides" }).click();
    await expect(page.locator("h1")).toHaveText("Slides");
  });

  test("It navigates to media list", async ({ page }) => {
    await page.route("**/media*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    await page.getByRole("link", { name: "Medier" }).click();
    await expect(page.locator("h1")).toHaveText("Medier");
  });

  test("It navigates to screens list", async ({ page }) => {
    await page.route("**/screens*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });
    await page.getByRole("link", { name: "Skærme" }).click();
    await expect(page.locator("h1")).toHaveText("Skærme");
  });

  test("It navigates to groups list", async ({ page }) => {
    await page.route("**/screen-groups*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    await page.getByRole("link", { name: "Grupper" }).click();
    await expect(page.locator("h1")).toHaveText("Grupper");
  });

  test("It navigates to playlists list", async ({ page }) => {
    await page.route("**/playlists*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    await page.getByRole("link", { name: "Spillelister", exact: true }).click();
    await expect(page.locator("h1")).toHaveText("Spillelister");
  });

  test("It navigates to themes list", async ({ page }) => {
    await page.route("**/themes*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    await page.getByRole("link", { name: "Temaer" }).click();
    await expect(page.locator("h1")).toHaveText("Temaer");
  });

  test("It navigates to create slide", async ({ page }) => {
    await page.route("**/playlists*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });
    await page.route("**/themes*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });
    await page.route("**/templates*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });

    await page.getByLabel("Tilføj nyt slide").first().click();
    await expect(page.locator("h1")).toHaveText("Opret nyt slide:");
  });

  test("It navigates to create playlist", async ({ page }) => {
    await page.route("**/tenants*", async (route) => {
      await route.fulfill({ json: emptyJson });
    });
    await page.getByLabel("Tilføj ny spilleliste").first().click();
    await expect(page.locator("h1")).toHaveText("Opret ny spilleliste:");
  });

  test("It navigates to create screen", async ({ page }) => {
    await page.getByLabel("Tilføj ny skærm").first().click();
    await expect(page.locator("h1")).toHaveText("Opret ny skærm");
  });

  test("It loads different menu on smaller screens", async ({ page }) => {
    await page.setViewportSize({ width: 550, height: 750 });
    await expect(page.locator("#basic-navbar-nav-burger")).toBeVisible();
    await expect(page.locator(".name")).toBeVisible();
    await expect(page.locator("#top-bar-brand")).toBeVisible();
    await expect(page.locator("#sidebar")).not.toBeVisible();
    await expect(page.locator("#topbar_signout")).not.toBeVisible();
    await page.locator("#basic-navbar-nav-burger").click();
    await expect(page.locator("#basic-navbar-nav")).toBeVisible();
    await expect(
      page.locator("#basic-navbar-nav").locator(".nav-item"),
    ).toHaveCount(14);
    await expect(
      page.locator("#basic-navbar-nav").locator(".nav-add-new"),
    ).toHaveCount(3);
    await expect(
      page.locator("#basic-navbar-nav").locator("#topbar_signout"),
    ).toBeVisible();
  });
});
