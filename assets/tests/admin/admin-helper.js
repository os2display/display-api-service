import {
  emptySlidesJson,
  feedSourcesJson,
  tokenJson,
} from "./data-fixtures.js";
import { expect } from "@playwright/test";

const loginTest = async ({ page }) => {
  await page.goto("/admin/slides/list");

  // Abort all routes that are not registered.
  await page.route("**/*", async (route) => {
    await route.abort();
  });

  await page.route("**/token", async (route) => {
    await route.fulfill({ json: tokenJson });
  });

  await page.route("**/slides*", async (route) => {
    await route.fulfill({ json: emptySlidesJson });
  });

  await expect(page).toHaveTitle(/OS2Display Admin/);
  await page.getByLabel("Email").fill("admin@example.com");
  await page.getByLabel("Kodeord").fill("password");
  await page.locator("#login").click();
  await expect(page.locator("h1").getByText("Slides")).toBeVisible();
};

export { loginTest };
