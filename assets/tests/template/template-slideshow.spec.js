import { test, expect } from "@playwright/test";

test("Slideshow 0", async ({ page }) => {
  await page.goto(
    "/template/slideshow-0"
  );

  // TODO
});

test("Slideshow 1", async ({ page }) => {
  await page.goto(
    "/template/slideshow-1-no-stuff"
  );

  // TODO
});

test("Slideshow 2", async ({ page }) => {
  await page.goto(
    "/template/slideshow-2"
  );

  // TODO
});
