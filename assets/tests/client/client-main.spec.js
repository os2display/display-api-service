import { test, expect } from "@playwright/test";

test("Test client index", async ({ page }) => {
  await page.goto(
    "/client"
  );

  await expect(page).toHaveTitle(/OS2Display Client/);
});
