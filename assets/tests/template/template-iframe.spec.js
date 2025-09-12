import { test, expect } from "@playwright/test";

test("iframe-0: ui tests", async ({ page }) => {
  await page.goto("/template/iframe-0");
  const iframe = page.locator("iframe");
  await expect(iframe).toBeVisible();
  await expect(iframe).toHaveAttribute(
    "src",
    "https://images.unsplash.com/photo-1551373884-8a0750074df7?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=2370&q=80",
  );
});
