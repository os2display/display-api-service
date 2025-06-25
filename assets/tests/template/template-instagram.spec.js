import { test, expect } from "@playwright/test";

test("Instagram 0", async ({ page }) => {
  await page.goto(
    "/template/instagram-0"
  );

  // TODO
});

test("Instagram 1", async ({ page }) => {
  await page.goto(
    "/template/instagram-1-no-max-entries"
  );

  // TODO
});
