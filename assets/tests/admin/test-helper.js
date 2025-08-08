import {
  accessConfigJson,
  adminConfigJson,
  emptyJson,
  feedSourcesJson,
  tokenAdminJson,
} from "./data-fixtures.js";
import { expect } from "@playwright/test";

const beforeEachTest = async (page) => {
  // Abort all routes that are not registered.
  await page.route("*", async (route) => {
    await route.abort();
  });

  // Handle calls to cccess-config.
  await page.route("**/access-config.json*", async (route) => {
    await route.fulfill({ json: accessConfigJson });
  });

  // Handle all calls to config.
  await page.route("**/config/admin", async (route) => {
    await route.fulfill({ json: adminConfigJson });
  });
};

const awaitEmptyRoutes = async (page, routePatterns) => {
  for (const routePattern of routePatterns) {
    await page.route(routePattern, async (route) => {
      await route.fulfill({ json: emptyJson });
    });
  }
}

const awaitDataRoute = async (page, routePattern, data, status) => {
  const result = { json: data};

  if (status) {
    result['status'] = status;
  }

  await page.route(routePattern, async (route) => {
    await route.fulfill(result);
  });
}

const loginTest = async ({ page }) => {
  await page.goto("/admin/slides/list");

  await page.route("**/token", async (route) => {
    await route.fulfill({ json: tokenAdminJson });
  });

  await page.route("**/slides*", async (route) => {
    await route.fulfill({ json: emptyJson });
  });

  await expect(page).toHaveTitle(/OS2Display Admin/);
  await page.getByLabel("Email").fill("admin@example.com");
  await page.getByLabel("Kodeord").fill("password");
  await page.locator("#login").click();
  await expect(page.locator("h1").getByText("Slides")).toBeVisible();
};

export { loginTest, beforeEachTest, awaitEmptyRoutes, awaitDataRoute };
