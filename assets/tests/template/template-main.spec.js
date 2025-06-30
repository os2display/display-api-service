import { test, expect } from "@playwright/test";

test("Test template index", async ({ page }) => {
  await page.goto(
    "/template"
  );

  await expect(page).toHaveTitle(/OS2Display Templates/);
  await expect(page.getByText("Examples")).toBeVisible();
});
