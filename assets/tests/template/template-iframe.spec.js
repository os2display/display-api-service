import { test, expect } from "@playwright/test";

test("IFrame 0", async ({ page }) => {
  await page.goto(
    "/template/iframe-0"
  );

  // TODO
});
