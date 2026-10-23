import { test, expect } from "@playwright/test";
import {
  fulfillDataRoute,
  fulfillEmptyRoutes,
  beforeEachTest,
  loginTest,
} from "./test-helper.js";
import {
  emptyJson,
  playlistSingleJson,
  screensListJson,
  slidesJson1,
} from "./data-fixtures.js";

test.describe("Campaign pages work", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillEmptyRoutes(page, [
      "**/playlists*",
      "**/screens*",
      "**/screen-groups*",
    ]);

    await page.locator(".sidebar-nav .nav-link").getByText("Kampagner").click();
    await expect(page.locator("h1").getByText("Kampagner")).toBeVisible();
    await page.getByText("Opret ny kampagne").click();
  });

  test("It cancels create campaign", async ({ page }) => {
    await expect(page.locator("#cancel_playlist")).toBeVisible();
    await page.locator("#cancel_playlist").click();
    await expect(page.locator("#cancel_playlist")).not.toBeVisible();
  });

  test("It removes slide", async ({ page }) => {
    // Intercept slides in dropdown
    await fulfillDataRoute(page, "**/slides*", slidesJson1);
    await fulfillDataRoute(page, "**/playlists/*/slides*", emptyJson);

    // Pick slide
    await page
      .locator("#slides-section")
      .locator(".dropdown-container")
      .nth(0)
      .press("Enter");
    await page
      .locator("#slides-section")
      .locator(".search")
      .locator('[type="text"]')
      .fill("d");
    await page.locator(".dropdown-content").locator("li").nth(1).click();
    await page
      .locator("#slides-section")
      .locator(".dropdown-container")
      .nth(0)
      .click();
    await expect(
      page.locator("#slides-section").locator("tbody").locator("tr td"),
    ).toHaveCount(6);

    // Remove slide
    await page.locator(".remove-from-list").click();

    // See that slides section is removed.
    await expect(page.getByText("Afspilningsrækkefølge")).not.toBeVisible();
  });
});

test.describe("Campaign save", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
    // Catch-all, so no call reaches the real API and logs the admin out.
    await fulfillEmptyRoutes(page, ["**/v2/**"]);
    await loginTest(page);

    await fulfillDataRoute(page, "**/screens*", screensListJson);
    await page.route(
      (url) => url.pathname === "/v2/playlists",
      (route) =>
        route.request().method() === "POST"
          ? route.fulfill({ json: playlistSingleJson })
          : route.fallback(),
    );

    await page.locator(".sidebar-nav .nav-link").getByText("Kampagner").click();
    await expect(page.locator("h1").getByText("Kampagner")).toBeVisible();
    await page.getByText("Opret ny kampagne").click();

    await page.getByLabel("Spillelistens navn").fill("Kampagne");
    const screens = page.locator(
      '.dropdown-container[aria-labelledby="screens"]',
    );
    await screens.press("Enter");
    await screens
      .locator(".dropdown-content")
      .getByText("Ab eos dolorum minima inventore.")
      .click();
    await screens.press("Escape");
    await page.getByLabel("Udgiv fra").fill("2030-01-01T10:00");
  });

  test("It saves a campaign with a selected screen", async ({ page }) => {
    await page.getByLabel("Udgiv til").fill("2030-01-02T10:00");

    const screensPut = page.waitForRequest(
      (request) =>
        request.method() === "PUT" &&
        /\/screens\/[A-Z0-9]{26}\/campaigns$/.test(request.url()),
    );
    await page.locator("#save_slide_and_close").click();
    const request = await screensPut;

    // The path holds the campaign id, the body the screens.
    expect(new URL(request.url()).pathname).toBe(
      "/v2/screens/004ZP1XQ1G1MVZ1T0100YN0MPC/campaigns",
    );
    expect(request.postDataJSON()).toEqual([
      { screen: "00APXK73HQ11PM0X3P12EG14DZ" },
    ]);
    await page.waitForURL("**/campaign/list");
  });

  test("It does not save a campaign without a publish end", async ({
    page,
  }) => {
    const writes = [];
    page.on("request", (request) => {
      if (request.method() !== "GET") {
        writes.push(`${request.method()} ${request.url()}`);
      }
    });

    await page.locator("#save_slide_and_close").click();

    await expect(page.locator('[id="published.to"]')).toHaveClass(/is-invalid/);
    await expect(page.locator('[id="published.from"]')).not.toHaveClass(
      /is-invalid/,
    );
    expect(writes).toEqual([]);
  });
});
