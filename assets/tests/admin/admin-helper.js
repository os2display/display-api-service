import {
  accessConfigJson, adminConfigJson,
  emptyJson,
  feedSourcesJson,
  tokenJson
} from "./data-fixtures.js";
import { expect } from "@playwright/test";

const abortUnhandledRoutes = async(page) => {
  // Abort all routes that are not registered.
  await page.route("*", async (route) => {
    await route.abort();
  });
};

const loginTest = async ({ page }) => {
  await page.goto("/admin/slides/list");

  await page.route("**/token", async (route) => {
    await route.fulfill({ json: tokenJson });
  });

  await page.route('**/access-config.json*', async (route) => {
    await route.fulfill({ json: accessConfigJson});
  })

  await page.route('**/config/admin', async (route) => {
    await route.fulfill({ json: adminConfigJson });
  })

  await page.route("**/slides*", async (route) => {
    await route.fulfill({ json: emptyJson });
  });

  await expect(page).toHaveTitle(/OS2Display Admin/);
  await page.getByLabel("Email").fill("admin@example.com");
  await page.getByLabel("Kodeord").fill("password");
  await page.locator("#login").click();
  await expect(page.locator("h1").getByText("Slides")).toBeVisible();
};

export { loginTest, abortUnhandledRoutes };
