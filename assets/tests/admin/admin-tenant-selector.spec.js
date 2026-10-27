import { test, expect } from "@playwright/test";
import { adminConfigJson, tokenTenantsJson } from "./data-fixtures.js";
import { fulfillDataRoute, fulfillEmptyRoutes } from "./test-helper.js";

const longTitle =
  "Region Hovedstadens Psykiatri – Børne- og Ungdomspsykiatrisk Center";

test.describe("Tenant selector", () => {
  test.beforeEach(async ({ page }) => {
    await page.route("**/config/admin", async (route) => {
      await route.fulfill({ json: adminConfigJson });
    });
    await page.goto("/admin/slides/list");
    await fulfillEmptyRoutes(page, ["**/slides*"]);

    const [first, ...rest] = tokenTenantsJson.tenants;
    await fulfillDataRoute(page, "**/token", {
      ...tokenTenantsJson,
      tenants: [{ ...first, title: longTitle }, ...rest],
    });
    await page.locator("#login").click();

    await page.locator(".dropdown-container").click();
    await page.getByRole("option", { name: longTitle }).click();
  });

  test("A long tenant name is cut off instead of widening the sidebar", async ({
    page,
  }) => {
    const title = page.locator(".tenant-dropdown__title");
    await expect(title).toHaveText(longTitle);
    await expect(title).toHaveAttribute("title", longTitle);

    const sidebar = await page.locator("#sidebar").boundingBox();
    expect(sidebar.width).toBeLessThan(400);
    expect(await title.evaluate((el) => el.scrollWidth > el.clientWidth)).toBe(
      true,
    );
  });

  test("The open menu fits a long name beyond the sidebar", async ({
    page,
  }) => {
    await page.locator(".tenant-dropdown .dropdown-toggle").click();
    const item = page
      .locator(".tenant-dropdown .dropdown-item")
      .filter({ hasText: longTitle });
    await expect(item).toBeVisible();

    const sidebar = await page.locator("#sidebar").boundingBox();
    const menu = await page
      .locator(".tenant-dropdown .dropdown-menu")
      .boundingBox();
    expect(menu.x + menu.width).toBeGreaterThan(sidebar.x + sidebar.width);
    expect(await item.evaluate((el) => el.scrollWidth <= el.clientWidth)).toBe(
      true,
    );
  });

  test("The dropdown lines up with the add icons", async ({ page }) => {
    const dropdown = await page.locator(".tenant-dropdown").boundingBox();
    const icon = await page.locator(".nav-add-new svg").first().boundingBox();
    expect(Math.round(dropdown.x + dropdown.width)).toBe(
      Math.round(icon.x + icon.width),
    );

    // The caret is a border triangle: any padding would flatten it.
    const caret = await page
      .locator(".tenant-dropdown .dropdown-toggle")
      .evaluate((el) => {
        const after = getComputedStyle(el, "::after");
        return {
          width: parseFloat(after.width),
          borders:
            parseFloat(after.borderLeftWidth) +
            parseFloat(after.borderRightWidth),
        };
      });
    expect(caret.width).toBe(caret.borders);
  });
});
