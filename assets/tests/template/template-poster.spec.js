import { test, expect } from "@playwright/test";

test("Poster 0", async ({ page }) => {
  await page.goto(
    "/template/poster-0-single"
  );

  // TODO
});

test("Poster 1", async ({ page }) => {
  await page.goto(
    "/template/poster-1-subscription"
  );

  // TODO
});

test("Poster 2", async ({ page }) => {
  await page.goto(
    "/template/poster-2-single-no-feed"
  );

  // TODO
});
