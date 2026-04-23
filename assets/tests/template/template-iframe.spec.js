import { test, expect } from "@playwright/test";

test("iframe-0: ui tests", async ({ page }) => {
  await page.goto("/template/iframe-0");
  const iframe = page.locator("iframe");
  await expect(iframe).toBeVisible();
  await expect(iframe).toHaveAttribute(
    "src",
    "https://display.local.itkdev.dk/docs",
  );
});
